<?php
/**
 * Unit tests for User_Get endpoint.
 *
 * @package AthleteDashboard\Features\Profile\Tests\Unit\API\Endpoints
 */

namespace AthleteDashboard\Features\Profile\Tests\Unit\API\Endpoints;

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
	 * Profile service mock.
	 *
	 * @var Profile_Service|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $service;

	/**
	 * Response factory instance.
	 *
	 * @var Response_Factory
	 */
	private $response_factory;

	/**
	 * Test user ID.
	 *
	 * @var int
	 */
	private $test_user_id;

	/**
	 * Set up test environment.
	 */
	public function set_up() {
		parent::set_up();

		// Create test user
		$this->test_user_id = $this->factory->user->create(
			array(
				'role'         => 'subscriber',
				'user_login'   => 'testuser',
				'user_email'   => 'test@example.com',
				'display_name' => 'Test User',
			)
		);

		// Mock profile service
		$this->service = $this->createMock( Profile_Service::class );

		// Create response factory
		$this->response_factory = new Response_Factory();

		// Set current user
		wp_set_current_user( $this->test_user_id );
	}

	/**
	 * Test successful user data retrieval.
	 */
	public function test_get_user_success() {
		// Set up test data
		$user_data = array(
			'id'           => $this->test_user_id,
			'username'     => 'testuser',
			'email'        => 'test@example.com',
			'display_name' => 'Test User',
		);

		// Configure mock
		$this->service->expects( $this->once() )
			->method( 'get_user_data' )
			->with( $this->test_user_id )
			->willReturn( $user_data );

		// Create endpoint instance
		$endpoint = new User_Get( $this->service, $this->response_factory );

		// Create request
		$request = new WP_REST_Request( 'GET', '/athlete-dashboard/v1/profile/user' );
		$request->set_param( 'user_id', $this->test_user_id );

		// Make request
		$response = $endpoint->handle_request( $request );
		$data     = $response->get_data();

		// Verify response
		$this->assertTrue( $data['success'] );
		$this->assertEquals( $user_data, $data['data'] );
		$this->assertNull( $data['error'] );
	}

	/**
	 * Test unauthorized access.
	 */
	public function test_get_user_unauthorized() {
		// Set user to 0 (not logged in)
		wp_set_current_user( 0 );

		// Create endpoint instance
		$endpoint = new User_Get( $this->service, $this->response_factory );

		// Create request
		$request = new WP_REST_Request( 'GET', '/athlete-dashboard/v1/profile/user' );
		$request->set_param( 'user_id', $this->test_user_id );

		// Make request
		$response = $endpoint->handle_request( $request );
		$data     = $response->get_data();

		// Verify response
		$this->assertFalse( $data['success'] );
		$this->assertEquals( 401, $data['error']['status'] );
		$this->assertEquals( 'unauthorized', $data['error']['code'] );
	}

	/**
	 * Test user not found.
	 */
	public function test_get_user_not_found() {
		// Configure mock to return error
		$this->service->expects( $this->once() )
			->method( 'get_user_data' )
			->with( $this->test_user_id )
			->willReturn(
				new WP_Error(
					'not_found',
					'User not found',
					array( 'status' => 404 )
				)
			);

		// Create endpoint instance
		$endpoint = new User_Get( $this->service, $this->response_factory );

		// Create request
		$request = new WP_REST_Request( 'GET', '/athlete-dashboard/v1/profile/user' );
		$request->set_param( 'user_id', $this->test_user_id );

		// Make request
		$response = $endpoint->handle_request( $request );
		$data     = $response->get_data();

		// Verify response
		$this->assertFalse( $data['success'] );
		$this->assertEquals( 404, $data['error']['status'] );
		$this->assertEquals( 'not_found', $data['error']['code'] );
	}

	/**
	 * Test server error handling.
	 */
	public function test_get_user_server_error() {
		// Configure mock to throw exception
		$this->service->expects( $this->once() )
			->method( 'get_user_data' )
			->with( $this->test_user_id )
			->willThrowException( new \Exception( 'Database error' ) );

		// Create endpoint instance
		$endpoint = new User_Get( $this->service, $this->response_factory );

		// Create request
		$request = new WP_REST_Request( 'GET', '/athlete-dashboard/v1/profile/user' );
		$request->set_param( 'user_id', $this->test_user_id );

		// Make request
		$response = $endpoint->handle_request( $request );
		$data     = $response->get_data();

		// Verify response
		$this->assertFalse( $data['success'] );
		$this->assertEquals( 500, $data['error']['status'] );
		$this->assertEquals( 'server_error', $data['error']['code'] );
	}

	/**
	 * Test missing user ID parameter.
	 */
	public function test_get_user_missing_id() {
		// Create endpoint instance
		$endpoint = new User_Get( $this->service, $this->response_factory );

		// Create request without user_id
		$request = new WP_REST_Request( 'GET', '/athlete-dashboard/v1/profile/user' );

		// Make request
		$response = $endpoint->handle_request( $request );
		$data     = $response->get_data();

		// Verify response
		$this->assertFalse( $data['success'] );
		$this->assertEquals( 400, $data['error']['status'] );
		$this->assertEquals( 'invalid_parameter', $data['error']['code'] );
	}
}
