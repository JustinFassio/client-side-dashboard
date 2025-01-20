<?php
/**
 * PHPUnit bootstrap file
 *
 * @package AthleteDashboard
 */

// Load composer autoloader
$autoloader = dirname( __DIR__ ) . '/vendor/autoload.php';
if ( ! file_exists( $autoloader ) ) {
	die( 'Please run `composer install` before running tests.' );
}
require_once $autoloader;

// Define WordPress root directory (3 levels up from themes directory)
$wp_root_dir = dirname( dirname( dirname( dirname( __DIR__ ) ) ) );

// Set up WordPress paths
define( 'ABSPATH', $wp_root_dir . '/' );
define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
define( 'WPINC', 'wp-includes' );

// Set up WordPress test configuration
putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/php/framework/config.php' );
putenv( 'WP_PHPUNIT__TABLE_PREFIX=wptests_' );

// Define WordPress constants
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_DISPLAY', true );
define( 'WP_MEMORY_LIMIT', '256M' );
define( 'WP_MAX_MEMORY_LIMIT', '256M' );
define( 'WP_CACHE', false );
define( 'DISABLE_WP_CRON', true );
define( 'WP_INSTALLING', true );

// Load WordPress test suite
$wp_tests_dir = dirname( __DIR__ ) . '/vendor/wp-phpunit/wp-phpunit';
if ( ! file_exists( $wp_tests_dir . '/includes/bootstrap.php' ) ) {
	die( 'WordPress test suite not found. Please run `composer install`.' );
}

// Load WordPress test functions first
require_once $wp_tests_dir . '/includes/functions.php';

// Set up WordPress test environment
tests_add_filter(
	'muplugins_loaded',
	function () {
		// Load your theme
		require dirname( __DIR__ ) . '/functions.php';
	}
);

// Load WordPress test bootstrap
require_once $wp_tests_dir . '/includes/bootstrap.php';

// Load mocks
require_once __DIR__ . '/mocks/wp-functions.php';
require_once __DIR__ . '/mocks/wp-rest-functions.php';

/**
 * Test case base class
 */
class AD_UnitTestCase extends \PHPUnit\Framework\TestCase {
	protected function setUp(): void {
		parent::setUp();
		$this->resetWPDBMock();
		$this->resetWPCacheMock();
		$this->resetWPRESTMock();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	protected function resetWPDBMock(): void {
		global $wpdb;
		$wpdb = $this->getMockBuilder( 'wpdb' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function resetWPCacheMock(): void {
		WP_Mock::userFunction(
			'wp_cache_get',
			array(
				'return' => false,
			)
		);
		WP_Mock::userFunction(
			'wp_cache_set',
			array(
				'return' => true,
			)
		);
	}

	protected function resetWPRESTMock(): void {
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
	}
}
