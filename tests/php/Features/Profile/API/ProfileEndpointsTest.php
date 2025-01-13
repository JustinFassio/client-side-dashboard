<?php
/**
 * Tests for the Profile endpoints.
 *
 * @package AthleteDashboard\Tests\Features\Profile\API
 */

namespace AthleteDashboard\Tests\Features\Profile\API;

use AthleteDashboard\Features\Profile\API\ProfileEndpoints;
use AthleteDashboard\Tests\Framework\TestCase;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Class ProfileEndpointsTest
 *
 * Tests the functionality of the Profile endpoints.
 *
 * @covers \AthleteDashboard\Features\Profile\API\ProfileEndpoints
 */
class ProfileEndpointsTest extends TestCase {
	/** @var WP_REST_Server REST server instance. */
	private $server;

	/** @var string Base endpoint route. */
	private $route = '/athlete-dashboard/v1/profile';

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
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
	public function tearDown(): void {
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
	 *
	 * @covers \AthleteDashboard\Features\Profile\API\ProfileEndpoints::get_profile
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
	 *
	 * @covers \AthleteDashboard\Features\Profile\API\ProfileEndpoints::update_profile
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
	 *
	 * @covers \AthleteDashboard\Features\Profile\API\ProfileEndpoints::check_auth
	 */
	public function test_unauthorized_access() {
		// Ensure no user is logged in.
		wp_set_current_user( 0 );

		// Make request.
		$response = $this->make_request( 'GET', $this->route );

		// Verify response.
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test validation of profile data.
	 *
	 * @covers \AthleteDashboard\Features\Profile\API\ProfileEndpoints::validate_profile_data
	 */
	public function test_profile_validation() {
		// Create test user.
		$user_id = $this->factory->user->create();
		wp_set_current_user( $user_id );

		// Test invalid data.
		$invalid_data = array(
			'age'    => 5, // Too young
			'height' => 400, // Too tall
			'weight' => 10, // Too light
			'email'  => 'not-an-email',
		);

		// Make request.
		$response = $this->make_request( 'POST', $this->route, $invalid_data );

		// Verify response indicates validation failure.
		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'validation_failed', $data['code'] );
	}
}
