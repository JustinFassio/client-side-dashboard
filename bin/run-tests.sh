#!/bin/bash

# Default values
ENV="development"
PARALLEL=false
DEBUG=false
RESET_DB=false
FILTER=""
REPORT_DIR="tests/reports"

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    key="$1"
    case $key in
        -e|--environment)
            ENV="$2"
            shift
            shift
            ;;
        -p|--parallel)
            PARALLEL=true
            shift
            ;;
        -d|--debug)
            DEBUG=true
            shift
            ;;
        -r|--reset-db)
            RESET_DB=true
            shift
            ;;
        -f|--filter)
            FILTER="$2"
            shift
            shift
            ;;
        *)
            echo "Unknown option: $1"
            exit 1
            ;;
    esac
done

# Create reports directory if it doesn't exist
mkdir -p "$REPORT_DIR"

# Load environment variables
if [ -f ".env.$ENV" ]; then
    source ".env.$ENV"
else
    echo "Environment file .env.$ENV not found"
    exit 1
fi

# Reset database if requested
if [ "$RESET_DB" = true ]; then
    echo "Resetting test database..."
    wp db reset --yes
    wp core install --url=example.com --title=Test --admin_user=admin --admin_password=password --admin_email=admin@example.com
fi

# Build test command
CMD="vendor/bin/phpunit"

if [ "$DEBUG" = true ]; then
    CMD="$CMD --debug"
fi

if [ "$PARALLEL" = true ]; then
    CMD="$CMD --parallel"
fi

if [ ! -z "$FILTER" ]; then
    CMD="$CMD --filter=$FILTER"
fi

# Add reporting options
CMD="$CMD --coverage-html $REPORT_DIR/coverage"
CMD="$CMD --log-junit $REPORT_DIR/junit.xml"
CMD="$CMD --testdox-html $REPORT_DIR/testdox.html"

# Run tests
echo "Running tests with command: $CMD"
$CMD

# Check exit status
STATUS=$?

# Generate test summary
echo "Generating test summary..."
echo "Test Results Summary" > "$REPORT_DIR/summary.txt"
echo "===================" >> "$REPORT_DIR/summary.txt"
echo "Environment: $ENV" >> "$REPORT_DIR/summary.txt"
echo "Date: $(date)" >> "$REPORT_DIR/summary.txt"
echo "Status: $([ $STATUS -eq 0 ] && echo 'PASSED' || echo 'FAILED')" >> "$REPORT_DIR/summary.txt"

# Parse and add test counts
if [ -f "$REPORT_DIR/junit.xml" ]; then
    TESTS=$(grep -o 'tests="[0-9]*"' "$REPORT_DIR/junit.xml" | head -1 | cut -d'"' -f2)
    FAILURES=$(grep -o 'failures="[0-9]*"' "$REPORT_DIR/junit.xml" | head -1 | cut -d'"' -f2)
    ERRORS=$(grep -o 'errors="[0-9]*"' "$REPORT_DIR/junit.xml" | head -1 | cut -d'"' -f2)
    
    echo "Tests Run: $TESTS" >> "$REPORT_DIR/summary.txt"
    echo "Failures: $FAILURES" >> "$REPORT_DIR/summary.txt"
    echo "Errors: $ERRORS" >> "$REPORT_DIR/summary.txt"
fi

# Make script executable
chmod +x bin/run-tests.sh

exit $STATUS 