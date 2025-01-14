<?php

class EnvironmentTest extends WP_UnitTestCase {

	public function test_wordpress_constants() {
		// Test WordPress core constants
		$this->assertTrue( defined( 'ABSPATH' ), 'ABSPATH should be defined' );
		$this->assertTrue( file_exists( ABSPATH ), 'WordPress root directory should exist' );
		$this->assertTrue( file_exists( ABSPATH . 'wp-settings.php' ), 'wp-settings.php should exist' );

		// Test WordPress content paths
		$this->assertTrue( defined( 'WP_CONTENT_DIR' ), 'WP_CONTENT_DIR should be defined' );
		$this->assertTrue( file_exists( WP_CONTENT_DIR ), 'WordPress content directory should exist' );

		// Test database connection
		global $wpdb;
		$this->assertTrue( $wpdb->check_connection(), 'Database connection should be established' );
	}

	public function test_test_suite_setup() {
		// Test WordPress test constants
		$this->assertTrue( defined( 'WP_TESTS_DOMAIN' ), 'WP_TESTS_DOMAIN should be defined' );
		$this->assertTrue( defined( 'WP_TESTS_EMAIL' ), 'WP_TESTS_EMAIL should be defined' );
		$this->assertTrue( defined( 'WP_TESTS_TITLE' ), 'WP_TESTS_TITLE should be defined' );

		// Test WordPress test tables
		global $wpdb;
		$this->assertNotEmpty( $wpdb->prefix, 'Database prefix should be set' );
		$this->assertTrue( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}posts'" ), 'WordPress tables should exist' );
	}
}
