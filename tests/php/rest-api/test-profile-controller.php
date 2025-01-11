<?php
namespace AthleteDashboard\Tests\RestApi;

use AthleteDashboard\RestApi\Profile_Controller;
use WP_UnitTestCase;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Tests for the Profile_Controller class.
 *
 * @package Athlete_Dashboard
 */

/**
 * Class Profile_Controller_Test
 *
 * Test cases for the Profile_Controller class.
 */
class Profile_Controller_Test extends WP_UnitTestCase {
	/**
	 * Instance of the Profile_Controller class.
	 *
	 * @var Profile_Controller
	 */
	private $controller;

	/**
	 * Test user ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Test profile data.
	 *
	 * @var array
	 */
	private $profile_data;

	/**
	 * Test equipment data.
	 *
	 * @var array
	 */
	private $equipment_data;

	/**
	 * Set up test environment.
	 */
	protected function setUp() {
		parent::setUp();

		// Create a test user.
		$this->user_id = $this->factory->user->create();

		// Create test profile data.
		$this->profile_data = array(
			'name'        => 'Test User',
			'email'       => 'test@example.com',
			'preferences' => array(
				'notifications' => true,
				'theme'         => 'dark',
			),
		);

		// Create test equipment data.
		$this->equipment_data = array(
			'name'     => 'Test Equipment',
			'type'     => 'weights',
			'quantity' => 1,
		);

		// Initialize the controller.
		$this->controller = new Profile_Controller();
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		// Verify routes are registered correctly.
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/athlete-dashboard/v1/profile', $routes );
	}

	/**
	 * Test profile retrieval.
	 */
	public function test_get_profile() {
		// Set up test data.
		wp_set_current_user( $this->user_id );
		update_user_meta( $this->user_id, 'athlete_profile', $this->profile_data );

		// Make the request.
		$request  = new WP_REST_Request( 'GET', '/athlete-dashboard/v1/profile' );
		$response = $this->server->dispatch( $request );

		// Verify response.
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( $this->profile_data['name'], $data['name'] );
	}

	/**
	 * Test profile update.
	 */
	public function test_update_profile() {
		// Set up test data.
		wp_set_current_user( $this->user_id );

		// Make the request.
		$request = new WP_REST_Request( 'POST', '/athlete-dashboard/v1/profile' );
		$request->set_body_params( $this->profile_data );
		$response = $this->server->dispatch( $request );

		// Verify response.
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( $this->profile_data['name'], $data['name'] );

		// Verify database update.
		$stored_data = get_user_meta( $this->user_id, 'athlete_profile', true );
		$this->assertEquals( $this->profile_data['name'], $stored_data['name'] );
	}

	/**
	 * Test bulk profile update.
	 */
	public function test_bulk_update_profiles() {
		// Set up test data.
		wp_set_current_user( $this->admin_id );
		$profiles = array(
			array(
				'user_id' => $this->user_id,
				'profile' => $this->profile_data,
			),
		);

		// Make the request.
		$request = new WP_REST_Request( 'POST', '/athlete-dashboard/v1/profiles/bulk' );
		$request->set_body_params( array( 'profiles' => $profiles ) );
		$response = $this->server->dispatch( $request );

		// Verify response.
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertCount( 1, $data['updated'] );

		// Verify database updates.
		$stored_data = get_user_meta( $this->user_id, 'athlete_profile', true );
		$this->assertEquals( $this->profile_data['name'], $stored_data['name'] );
	}

	/**
	 * Test permission checks.
	 */
	public function test_permission_checks() {
		// Test unauthorized access.
		wp_set_current_user( 0 );
		$request  = new WP_REST_Request( 'GET', '/athlete-dashboard/v1/profile' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );

		// Test authorized access.
		wp_set_current_user( $this->user_id );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test transaction rollback.
	 */
	public function test_transaction_rollback() {
		// Set up test data.
		wp_set_current_user( $this->admin_id );
		$profiles = array(
			array(
				'user_id' => $this->user_id,
				'profile' => $this->profile_data,
			),
			array(
				'user_id' => 999999, // Invalid user ID.
				'profile' => $this->profile_data,
			),
		);

		// Make the request.
		$request = new WP_REST_Request( 'POST', '/athlete-dashboard/v1/profiles/bulk' );
		$request->set_body_params( array( 'profiles' => $profiles ) );
		$response = $this->server->dispatch( $request );

		// Verify rollback.
		$this->assertEquals( 400, $response->get_status() );
		$stored_data = get_user_meta( $this->user_id, 'athlete_profile', true );
		$this->assertEmpty( $stored_data );
	}

	/**
	 * Test rate limiting.
	 */
	public function test_rate_limiting() {
		// Set up test data.
		wp_set_current_user( $this->user_id );

		// Make multiple requests.
		for ( $i = 0; $i < 10; $i++ ) {
			$request  = new WP_REST_Request( 'GET', '/athlete-dashboard/v1/profile' );
			$response = $this->server->dispatch( $request );
		}

		// Verify rate limit.
		$this->assertEquals( 429, $response->get_status() );
	}

	/**
	 * Test error handling.
	 */
	public function test_error_handling() {
		// Test invalid data.
		wp_set_current_user( $this->user_id );
		$request = new WP_REST_Request( 'POST', '/athlete-dashboard/v1/profile' );
		$request->set_body_params( array( 'invalid' => 'data' ) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );

		// Test server error.
		add_filter(
			'pre_update_user_meta',
			function () {
				return false;
			}
		);
		$request->set_body_params( $this->profile_data );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 500, $response->get_status() );
		remove_filter(
			'pre_update_user_meta',
			function () {
				return false;
			}
		);
	}
}
