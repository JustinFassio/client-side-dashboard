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
	 * Mock service for testing.
	 *
	 * @var Profile_Service
	 */
	private $service;

	/**
	 * Mock response factory for testing.
	 *
	 * @var Response_Factory
	 */
	private $response_factory;

	/**
	 * Mock registry for testing.
	 *
	 * @var Endpoint_Registry
	 */
	private $registry;

	/**
	 * Set up test environment.
	 */
	public function set_up() {
		parent::set_up();

		error_log( '=== Setting up Profile_Routes_Test ===' );

		// Create a test user
		$this->test_user_id = $this->factory->user->create(
			array(
				'role' => 'subscriber',
			)
		);
		error_log( 'Created test user with ID: ' . $this->test_user_id );

		// Initialize REST server first
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		error_log( 'Initialized REST server' );

		// Set up dependencies with mock service
		$this->service          = $this->createMock( Profile_Service::class );
		$this->response_factory = new Response_Factory();
		$this->registry         = new Endpoint_Registry();

		// Create routes instance
		$this->routes = new Profile_Routes( $this->service, $this->response_factory, $this->registry );
		$this->routes->init();
		error_log( 'Initialized Profile_Routes' );

		// Now trigger REST API initialization
		do_action( 'rest_api_init' );
		error_log( 'Triggered rest_api_init' );

		// Log all registered routes
		error_log( 'All registered routes after setup:' );
		error_log( print_r( rest_get_server()->get_routes(), true ) );
	}

	/**
	 * Test that the user get endpoint is registered.
	 */
	public function test_user_get_endpoint_registration() {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/athlete-dashboard/v1/profile/user', $routes );

		$route = $routes['/athlete-dashboard/v1/profile/user'][0];
		$this->assertEquals( array( 'GET' => true ), $route['methods'] );
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
		error_log( '=== Starting test_user_can_access_own_profile ===' );
		wp_set_current_user( $this->test_user_id );
		error_log( 'Set current user to: ' . $this->test_user_id );

		// Mock the service to return test profile data
		$profile_data = array(
			'age'             => 30,
			'gender'          => 'male',
			'heightCm'        => 180,
			'weightKg'        => 80,
			'activityLevel'   => 'moderate',
			'experienceLevel' => 'intermediate',
			'equipment'       => array( 'dumbbells', 'barbell' ),
			'fitnessGoals'    => array( 'strength', 'muscle' ),
		);

		$this->service->expects( $this->once() )
			->method( 'get_profile' )
			->with( $this->test_user_id )
			->willReturn( $profile_data );

		$request = new WP_REST_Request( 'GET', '/athlete-dashboard/v1/profile/user' );
		$request->set_param( 'user_id', $this->test_user_id );
		error_log( 'Created request for route: ' . $request->get_route() );

		$response = rest_get_server()->dispatch( $request );
		error_log( 'Response status: ' . $response->get_status() );
		error_log( 'Response data: ' . print_r( $response->get_data(), true ) );

		$this->assertEquals( 200, $response->get_status(), 'Expected 200 status code but got ' . $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'data', $data );
		$this->assertArrayHasKey( 'id', $data['data'] );
		$this->assertEquals( $this->test_user_id, $data['data']['id'] );
		$this->assertArrayHasKey( 'profile', $data['data'] );
		$this->assertEquals( $profile_data, $data['data']['profile'] );
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
		$data = $response->get_data();
		$this->assertEquals( 'rest_forbidden', $data['code'] );
	}

	/**
	 * Clean up test environment.
	 */
	public function tear_down() {
		parent::tear_down();
		wp_delete_user( $this->test_user_id );
	}
}
