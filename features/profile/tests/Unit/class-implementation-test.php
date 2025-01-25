<?php
/**
 * Implementation verification test.
 *
 * @package AthleteDashboard\Features\Profile\Tests\Unit
 */

namespace AthleteDashboard\Features\Profile\Tests\Unit;

use WP_UnitTestCase;
use AthleteDashboard\Features\Profile\Profile_Bootstrap;
use AthleteDashboard\Core\Container;
use AthleteDashboard\Features\Profile\API\Profile_Routes;

/**
 * Class Implementation_Test
 */
class Implementation_Test extends WP_UnitTestCase {
	/**
	 * Container instance.
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Profile bootstrap instance.
	 *
	 * @var Profile_Bootstrap
	 */
	private $bootstrap;

	/**
	 * Set up test environment.
	 */
	public function set_up() {
		parent::set_up();
		$this->container = new Container();
		$this->bootstrap = new Profile_Bootstrap();
	}

	/**
	 * Test that our implementation is properly hooked into WordPress.
	 */
	public function test_implementation_hooks() {
		global $wp_filter;

		// Bootstrap the feature
		$this->bootstrap->bootstrap( $this->container );

		// Verify rest_api_init action is hooked
		$this->assertTrue(
			has_action( 'rest_api_init' ),
			'rest_api_init action is not hooked'
		);

		// Verify our routes are registered
		do_action( 'rest_api_init' );
		$routes = rest_get_server()->get_routes();

		// Debug output
		error_log( 'Registered routes: ' . print_r( $routes, true ) );

		// Check for our specific routes
		$this->assertArrayHasKey(
			'/athlete-dashboard/v1/profile/user',
			$routes,
			'Profile user endpoint is not registered'
		);

		// Verify container bindings
		$this->assertTrue(
			$this->container->has( Profile_Routes::class ),
			'Profile_Routes is not bound in container'
		);

		// Test actual endpoint response
		$request  = new \WP_REST_Request( 'GET', '/athlete-dashboard/v1/profile/user' );
		$response = rest_do_request( $request );

		error_log( 'Test endpoint response: ' . print_r( $response, true ) );

		// Verify response structure
		$this->assertEquals(
			401,  // Should be unauthorized since we're not logged in
			$response->get_status(),
			'Endpoint is not returning correct unauthorized status'
		);
	}

	/**
	 * Test that legacy code is not interfering.
	 */
	public function test_legacy_interference() {
		global $wp_filter;

		// Check for any legacy hooks that might interfere
		$legacy_hooks = array(
			'athlete_dashboard_register_rest_routes',
			'athlete_dashboard_init_profile',
			'athlete_dashboard_profile_endpoints',
		);

		foreach ( $legacy_hooks as $hook ) {
			$this->assertFalse(
				has_action( $hook ),
				"Legacy hook '$hook' is still active"
			);
		}

		// Check if old endpoint registration function exists
		$this->assertFalse(
			function_exists( 'athlete_dashboard_register_profile_endpoints' ),
			'Legacy endpoint registration function still exists'
		);
	}

	/**
	 * Test that our new implementation is taking precedence.
	 */
	public function test_implementation_precedence() {
		// Bootstrap the feature
		$this->bootstrap->bootstrap( $this->container );

		// Get all registered routes
		do_action( 'rest_api_init' );
		$routes = rest_get_server()->get_routes();

		// Count routes matching our namespace
		$profile_routes = array_filter(
			array_keys( $routes ),
			function ( $route ) {
				return strpos( $route, 'athlete-dashboard/v1/profile' ) === 0;
			}
		);

		error_log( 'Profile routes found: ' . print_r( $profile_routes, true ) );

		// Verify only our new implementation routes exist
		$this->assertCount(
			1,  // We currently only register the user endpoint
			$profile_routes,
			'Unexpected number of profile routes registered'
		);
	}
}
