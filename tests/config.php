<?php
/**
 * Test configuration file
 */

// Database settings
define( 'DB_NAME', 'local' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', 'root' );
define( 'DB_HOST', 'localhost:/Users/justinfassio/Library/Application Support/Local/run/8U-IQPW3o/mysql/mysqld.sock' );

// WordPress paths
$wp_root_dir = dirname( dirname( dirname( dirname( __DIR__ ) ) ) );
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $wp_root_dir . '/' );
}
if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}
if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
}

// WordPress test environment settings
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', true );
}
if ( ! defined( 'WP_TESTS_DOMAIN' ) ) {
	define( 'WP_TESTS_DOMAIN', 'example.org' );
}
if ( ! defined( 'WP_TESTS_EMAIL' ) ) {
	define( 'WP_TESTS_EMAIL', 'admin@example.org' );
}
if ( ! defined( 'WP_TESTS_TITLE' ) ) {
	define( 'WP_TESTS_TITLE', 'Test Blog' );
}
if ( ! defined( 'WP_TESTS_NETWORK_TITLE' ) ) {
	define( 'WP_TESTS_NETWORK_TITLE', 'Test Network' );
}
if ( ! defined( 'WP_TESTS_MULTISITE' ) ) {
	define( 'WP_TESTS_MULTISITE', false );
}

// Table prefix for test database
$table_prefix = 'wptests_';
