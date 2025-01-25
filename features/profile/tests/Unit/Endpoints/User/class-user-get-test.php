<?php
/**
 * Unit tests for User_Get endpoint.
 *
 * @package AthleteDashboard\Features\Profile\Tests\Unit\Endpoints\User
 */

namespace AthleteDashboard\Features\Profile\Tests\Unit\Endpoints\User;

use AthleteDashboard\Features\Profile\API\Endpoints\User\User_Get;
use AthleteDashboard\Features\Profile\Services\Profile_Service;
use AthleteDashboard\Features\Profile\API\Response_Factory;
use WP_REST_Request;
use WP_UnitTestCase;
use WP_Error;

/**
 * Class User_Get_Test
 */
class User_Get_Test extends WP_UnitTestCase {
	/**
	 * Test user ID.
	 *
	 * @var int
	 */
	private $test_user_id;

	/**
	 * User_Get endpoint instance.
	 *
	 * @var User_Get
	 */
	private $endpoint;

	/**
	 * Set up test environment.
	 */
	public function set_up() {
		parent::set_up();

		// Create a test user
		$this->test_user_id = $this->factory->user->create(
			array(
				'role'         => 'subscriber',
				'user_login'   => 'testuser',
				'user_email'   => 'test@example.com',
				'display_name' => 'Test User',
			)
		);

		// Set current user
		wp_set_current_user( $this->test_user_id );

		// Initialize endpoint
		$service          = new Profile_Service();
		$response_factory = new Response_Factory();
		$this->endpoint   = new User_Get( $service, $response_factory );
	}

	/**
	 * Test endpoint route.
	 */
	public function test_get_route() {
		$this->assertEquals( '/user', $this->endpoint->get_route() );
	}

	/**
	 * Test endpoint method.
	 */
	public function test_get_method() {
		$this->assertEquals( 'GET', $this->endpoint->get_method() );
	}

	/**
	 * Test successful profile retrieval.
	 */
	public function test_handle_request_success() {
		// Create a mock request
		$request = new WP_REST_Request( 'GET', '/athlete-dashboard/v1/profile/user' );
		$request->set_param( 'user_id', $this->test_user_id );

		// Make the request
		$response = $this->endpoint->handle_request( $request );

		// Assert response structure
		$data = $response->get_data();
		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'profile', $data );
		$this->assertEquals( $this->test_user_id, $data['id'] );
	}

	/**
	 * Test unauthorized access.
	 */
	public function test_handle_request_unauthorized() {
		// Set current user to 0 (not logged in)
		wp_set_current_user( 0 );

		// Create a mock request
		$request = new WP_REST_Request( 'GET', '/athlete-dashboard/v1/profile/user' );
		$request->set_param( 'user_id', $this->test_user_id );

		// Make the request
		$response = $this->endpoint->handle_request( $request );

		// Assert it's a WP_Error
		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 401, $response->get_error_data()['status'] );
	}

	/**
	 * Test accessing another user's profile.
	 */
	public function test_handle_request_forbidden() {
		// Create another user
		$other_user_id = $this->factory->user->create();

		// Create a mock request for the other user's profile
		$request = new WP_REST_Request( 'GET', '/athlete-dashboard/v1/profile/user' );
		$request->set_param( 'user_id', $other_user_id );

		// Make the request
		$response = $this->endpoint->handle_request( $request );

		// Assert it's a WP_Error
		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 403, $response->get_error_data()['status'] );
	}

	/**
	 * Clean up after each test.
	 */
	public function tear_down() {
		wp_delete_user( $this->test_user_id );
		parent::tear_down();
	}
}
