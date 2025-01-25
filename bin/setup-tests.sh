#!/usr/bin/env bash

# Exit on error
set -e

# Function to show usage
show_usage() {
    echo "Usage: $0 [options]"
    echo ""
    echo "Options:"
    echo "  -h, --help                 Show this help message"
    echo "  -d, --db-name <name>       Database name (default: wordpress_test)"
    echo "  -u, --db-user <user>       Database user (default: root)"
    echo "  -p, --db-pass <pass>       Database password"
    echo "  --db-host <host>           Database host (default: localhost)"
    echo "  --db-port <port>           Database port (default: 3306)"
    echo "  --socket <path>            MySQL socket path"
    echo "  --mysql-bin <path>         Path to MySQL binary"
    echo "  --wp-version <version>     WordPress version (default: latest)"
    echo "  --skip-db-create          Skip database creation"
    echo ""
    exit 1
}

# Default values
DB_NAME="wordpress_test"
DB_USER="root"
DB_PASS=""
DB_HOST="localhost"
DB_PORT="3306"
DB_SOCKET=""
MYSQL_BIN="mysql"
WP_VERSION="latest"
SKIP_DB_CREATE="false"

# Try to find Local by Flywheel's MySQL client
find_local_mysql() {
    local mysql_paths=(
        "$HOME/Library/Application Support/Local/lightning-services/mysql-8.0.16+6/bin/darwin/bin/mysql"
        "/Applications/Local.app/Contents/Resources/lightning-services/mysql-8.0.16/bin/darwin/bin/mysql"
    )
    
    for mysql in "${mysql_paths[@]}"; do
        if [ -x "$mysql" ]; then
            echo "$mysql"
            return 0
        fi
    done
    
    return 1
}

# Try to find Local by Flywheel's MySQL client
if local_mysql=$(find_local_mysql); then
    MYSQL_BIN="$local_mysql"
    echo "Using Local by Flywheel MySQL client: $MYSQL_BIN"
fi

# Parse arguments
while [[ $# -gt 0 ]]; do
    key="$1"
    case $key in
        -h|--help)
            show_usage
            ;;
        -d|--db-name)
            DB_NAME="$2"
            shift
            shift
            ;;
        -u|--db-user)
            DB_USER="$2"
            shift
            shift
            ;;
        -p|--db-pass)
            DB_PASS="$2"
            shift
            shift
            ;;
        --db-host)
            # Extract host and port if provided in host:port format
            if [[ "$2" == *":"* ]]; then
                DB_HOST=$(echo "$2" | cut -d: -f1)
                DB_PORT=$(echo "$2" | cut -d: -f2)
            else
                DB_HOST="$2"
            fi
            shift
            shift
            ;;
        --db-port)
            DB_PORT="$2"
            shift
            shift
            ;;
        --socket)
            # Remove any existing escapes and requote
            DB_SOCKET=$(echo "$2" | sed 's/\\//g')
            shift
            shift
            ;;
        --mysql-bin)
            MYSQL_BIN="$2"
            shift
            shift
            ;;
        --wp-version)
            WP_VERSION="$2"
            shift
            shift
            ;;
        --skip-db-create)
            SKIP_DB_CREATE="true"
            shift
            ;;
        *)
            echo "Unknown option: $1"
            show_usage
            ;;
    esac
done

# Function to build MySQL connection string
build_mysql_connection() {
    local conn_str=()
    
    # Add user
    conn_str+=("-u" "$DB_USER")
    
    # Add password if provided
    if [ -n "$DB_PASS" ]; then
        conn_str+=("-p$DB_PASS")
    fi
    
    # Add socket if provided, otherwise use host and port
    if [ -n "$DB_SOCKET" ]; then
        if [ -S "$DB_SOCKET" ]; then
            conn_str+=("--socket=$DB_SOCKET")
        else
            echo "Warning: Socket file not found at $DB_SOCKET"
            # Fall back to TCP connection
            conn_str+=("-h" "$DB_HOST" "-P" "$DB_PORT")
        fi
    else
        conn_str+=("-h" "$DB_HOST" "-P" "$DB_PORT")
    fi
    
    printf "%q " "${conn_str[@]}"
}

# Function to test database connection
test_db_connection() {
    echo "Testing database connection..."
    echo "Host: $DB_HOST"
    echo "Port: $DB_PORT"
    echo "User: $DB_USER"
    echo "MySQL Client: $MYSQL_BIN"
    if [ -n "$DB_SOCKET" ]; then
        echo "Socket: $DB_SOCKET"
        if [ ! -S "$DB_SOCKET" ]; then
            echo "Warning: Socket file not found!"
        fi
    fi
    
    local conn_str
    conn_str=$(build_mysql_connection)
    
    echo "Testing connection with: $MYSQL_BIN $conn_str"
    if ! eval "\"$MYSQL_BIN\" $conn_str -e \"SELECT 1\" 2>&1"; then
        echo "Error: Could not connect to database. Please check your credentials."
        exit 1
    fi
    echo "Database connection successful!"
}

# Function to create test database
create_test_database() {
    if [ "$SKIP_DB_CREATE" = "true" ]; then
        echo "Skipping database creation..."
        return 0
    fi

    echo "Creating test database..."
    local conn_str
    conn_str=$(build_mysql_connection)
    
    echo "Creating database with: $MYSQL_BIN $conn_str"
    if ! eval "\"$MYSQL_BIN\" $conn_str -e \"DROP DATABASE IF EXISTS $DB_NAME; CREATE DATABASE $DB_NAME;\" 2>&1"; then
        echo "Error: Could not create test database."
        exit 1
    fi
    echo "Test database created successfully!"
}

# Main script
echo "Setting up WordPress test environment..."

# Test database connection
test_db_connection

# Create test database
create_test_database

# Install WordPress test suite
echo "Installing WordPress test suite..."
if [ -n "$DB_SOCKET" ] && [ -S "$DB_SOCKET" ]; then
    DB_HOST_ARG="localhost"
else
    DB_HOST_ARG="$DB_HOST:$DB_PORT"
fi

bash "$(dirname "$0")/install-wp-tests.sh" "$DB_NAME" "$DB_USER" "$DB_PASS" "$DB_HOST_ARG" "$WP_VERSION" "$SKIP_DB_CREATE" "$MYSQL_BIN" || {
    echo "Error: Could not install WordPress test suite."
    exit 1
}

echo "WordPress test environment setup complete!"
echo ""
echo "You can now run tests with:"
echo "WP_TESTS_DIR=/tmp/wordpress-tests-lib vendor/bin/phpunit" 