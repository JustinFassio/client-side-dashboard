<?php
/**
 * Profile Routes Test.
 *
 * @package AthleteDashboard\Features\Profile\Tests\Unit\API\Routes
 */

namespace AthleteDashboard\Features\Profile\Tests\Unit\API\Routes;

use WP_UnitTestCase;
use WP_REST_Server;
use WP_REST_Request;
use AthleteDashboard\Features\Profile\API\Profile_Routes;
use AthleteDashboard\Features\Profile\API\Registry\Endpoint_Registry;
use AthleteDashboard\Features\Profile\API\Response_Factory;
use AthleteDashboard\Features\Profile\Services\Profile_Service;

/**
 * Class Profile_Routes_Test
 */
class Profile_Routes_Test extends WP_UnitTestCase {
	/**
	 * Test user ID.
	 *
	 * @var int
	 */
	private $test_user_id;

	/**
	 * Profile routes instance.
	 *
	 * @var Profile_Routes
	 */
	private $routes;

	/**
	 * Set up test environment.
	 */
	public function set_up() {
		parent::set_up();

		// Create a test user
		$this->test_user_id = $this->factory->user->create(
			array(
				'role' => 'subscriber',
			)
		);

		// Set up dependencies
		$service          = $this->createMock( Profile_Service::class );
		$response_factory = new Response_Factory();
		$registry         = new Endpoint_Registry();

		// Create routes instance
		$this->routes = new Profile_Routes( $service, $response_factory, $registry );
		$this->routes->init();

		// Initialize REST server
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );
	}

	/**
	 * Test that the user get endpoint is registered.
	 */
	public function test_user_get_endpoint_registration() {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/athlete-dashboard/v1/profile/user', $routes );

		$route = $routes['/athlete-dashboard/v1/profile/user'][0];
		$this->assertEquals( 'GET', $route['methods'] );
		$this->assertArrayHasKey( 'permission_callback', $route );
		$this->assertArrayHasKey( 'args', $route );
	}

	/**
	 * Test that the endpoint requires authentication.
	 */
	public function test_user_get_endpoint_requires_auth() {
		$request  = new WP_REST_Request( 'GET', '/athlete-dashboard/v1/profile/user' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test that authenticated users can access their own profile.
	 */
	public function test_user_can_access_own_profile() {
		wp_set_current_user( $this->test_user_id );

		$request = new WP_REST_Request( 'GET', '/athlete-dashboard/v1/profile/user' );
		$request->set_param( 'user_id', $this->test_user_id );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test that users cannot access other users' profiles.
	 */
	public function test_user_cannot_access_other_profile() {
		wp_set_current_user( $this->test_user_id );

		$other_user_id = $this->factory->user->create(
			array(
				'role' => 'subscriber',
			)
		);

		$request = new WP_REST_Request( 'GET', '/athlete-dashboard/v1/profile/user' );
		$request->set_param( 'user_id', $other_user_id );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Clean up test environment.
	 */
	public function tear_down() {
		parent::tear_down();
		wp_delete_user( $this->test_user_id );
	}
}
