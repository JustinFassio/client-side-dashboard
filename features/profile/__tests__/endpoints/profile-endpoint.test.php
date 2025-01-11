<?php
/**
 * Tests for the Profile Endpoint functionality.
 *
 * @package Athlete_Dashboard
 */

/**
 * Test class for Profile Endpoint functionality.
 */
class Profile_Endpoint_Test extends TestCase {
	/** @var WP_REST_Server REST server instance. */
	private $server;

	/** @var string Base endpoint route. */
	private $route = '/athlete-dashboard/v1/profile';

	/**
	 * Set up test environment.
	 */
	public function set_up() {
		parent::setUp();

		// Initialize REST server.
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		do_action( 'rest_api_init' );
	}

	/**
	 * Clean up test environment.
	 */
	public function tear_down() {
		parent::tearDown();
		global $wp_rest_server;
		$wp_rest_server = null;
	}

	/**
	 * Helper function to make a REST API request.
	 *
	 * @param string $method   HTTP method.
	 * @param string $endpoint Endpoint path.
	 * @param array  $data     Request data.
	 * @return WP_REST_Response
	 */
	private function make_request( $method, $endpoint, $data = array() ) {
		$request = new WP_REST_Request( $method, $endpoint );
		if ( ! empty( $data ) ) {
			$request->set_body_params( $data );
		}
		return $this->server->dispatch( $request );
	}

	/**
	 * Helper function to compare response data.
	 *
	 * @param mixed $expected Expected data.
	 * @param mixed $actual   Actual data.
	 * @throws Exception If assertion fails.
	 */
	private function assert_response_data( $expected, $actual ) {
		$this->assertEquals(
			$expected,
			$actual,
			"Expected $expected but got $actual"
		);
	}

	/**
	 * Test that profile data can be retrieved successfully.
	 */
	public function test_get_profile() {
		// Create test user and set up data.
		$user_id = $this->factory->user->create();
		wp_set_current_user( $user_id );

		// Make request.
		$response = $this->make_request( 'GET', $this->route );

		// Verify response.
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'profile', $data );
	}

	/**
	 * Test that profile data can be updated successfully.
	 */
	public function test_update_profile() {
		// Create test user.
		$user_id = $this->factory->user->create();
		wp_set_current_user( $user_id );

		// Test data.
		$update_data = array(
			'name'  => 'Test User',
			'email' => 'test@example.com',
		);

		// Make request.
		$response = $this->make_request( 'POST', $this->route, $update_data );

		// Verify response.
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
	}

	/**
	 * Test that unauthorized users cannot access profile data.
	 */
	public function test_unauthorized_access() {
		// Ensure no user is logged in.
		wp_set_current_user( 0 );

		// Make request.
		$response = $this->make_request( 'GET', $this->route );

		// Verify response.
		$this->assertEquals( 401, $response->get_status() );
	}
}
