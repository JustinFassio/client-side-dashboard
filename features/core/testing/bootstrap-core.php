<?php
/**
 * Core Bootstrap for Feature Tests
 *
 * Provides minimal WordPress test environment setup.
 * Features should include this file in their own bootstrap.php
 *
 * @package AthleteDashboard\Features\Core\Testing
 */

// Composer autoloader
$autoloader = dirname( dirname( dirname( __DIR__ ) ) ) . '/vendor/autoload.php';
if ( ! file_exists( $autoloader ) ) {
	throw new RuntimeException( 'Composer autoloader not found. Please run composer install.' );
}
require_once $autoloader;

// WordPress test suite
$_tests_dir = dirname( dirname( dirname( __DIR__ ) ) ) . '/vendor/wp-phpunit/wp-phpunit';
if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	throw new RuntimeException( 'WordPress test suite not found. Please run composer install.' );
}

// Load WordPress test functionality
require_once $_tests_dir . '/includes/functions.php';

// Load core contracts early
require_once dirname( dirname( __DIR__ ) ) . '/core/contracts/interface-feature-contract.php';
require_once dirname( dirname( __DIR__ ) ) . '/core/contracts/class-abstract-feature.php';

tests_add_filter(
	'muplugins_loaded',
	function() {
		// Load minimal WordPress functionality needed for testing
	}
);

// Start up the WP testing environment
require $_tests_dir . '/includes/bootstrap.php'; 