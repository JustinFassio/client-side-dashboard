<?php
/**
 * Environment test to validate WordPress test setup.
 *
 * @package AthleteDashboard\Tests\Features\Profile
 */

use WP_UnitTestCase;

class EnvironmentTest extends WP_UnitTestCase {
	public function test_environment_setup() {
		$this->assertTrue( defined( 'ABSPATH' ), 'ABSPATH is not defined' );
		$this->assertTrue( defined( 'WP_CONTENT_DIR' ), 'WP_CONTENT_DIR is not defined' );
		$this->assertTrue( defined( 'WP_PLUGIN_DIR' ), 'WP_PLUGIN_DIR is not defined' );
		$this->assertTrue( defined( 'DB_NAME' ), 'DB_NAME is not defined' );
		$this->assertTrue( file_exists( ABSPATH . 'wp-settings.php' ), 'wp-settings.php not found' );
	}
}
