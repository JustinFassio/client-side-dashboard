<?php
/**
 * WordPress test environment configuration.
 *
 * @package AthleteDashboard\Tests\Framework
 */

// Test database settings
if ( ! defined( 'DB_NAME' ) ) {
	define( 'DB_NAME', 'local' );
}
if ( ! defined( 'DB_USER' ) ) {
	define( 'DB_USER', 'root' );
}
if ( ! defined( 'DB_PASSWORD' ) ) {
	define( 'DB_PASSWORD', 'root' );
}
if ( ! defined( 'DB_HOST' ) ) {
	define( 'DB_HOST', 'localhost:/Users/justinfassio/Library/Application Support/Local/run/8U-IQPW3o/mysql/mysqld.sock' );
}

// Test environment settings
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
