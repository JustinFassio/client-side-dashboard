<?php
/**
 * Profile Update Endpoint Test.
 *
 * @package AthleteDashboard\Features\Profile\Tests\Unit\API\Endpoints\Profile
 */

namespace AthleteDashboard\Features\Profile\Tests\Unit\API\Endpoints\Profile;

use AthleteDashboard\Features\Profile\API\Endpoints\Profile\Profile_Update;
use AthleteDashboard\Features\Profile\Services\Profile_Service;
use WP_REST_Request;
use WP_UnitTestCase;
use WP_Error;
use Mockery;

/**
 * Class Profile_Update_Test
 */
class Profile_Update_Test extends WP_UnitTestCase {
	/**
	 * @var Profile_Update
	 */
	private $endpoint;

	/**
	 * @var Profile_Service|Mockery\MockInterface
	 */
	private $profile_service;

	/**
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
				'role' => 'subscriber',
			)
		);

		// Mock profile service
		$this->profile_service = Mockery::mock( Profile_Service::class );

		// Create endpoint instance
		$this->endpoint = new Profile_Update();
		$this->endpoint->set_service( $this->profile_service );

		// Set current user
		wp_set_current_user( $this->test_user_id );
	}

	/**
	 * Clean up after test.
	 */
	public function tear_down() {
		Mockery::close();
		parent::tear_down();
	}

	/**
	 * Test successful profile update.
	 */
	public function test_update_profile_success() {
		$update_data = array(
			'firstName'   => 'John',
			'lastName'    => 'Doe',
			'email'       => 'john.doe@example.com',
			'displayName' => 'John Doe',
		);

		$expected_profile = array_merge( array( 'id' => $this->test_user_id ), $update_data );

		// Mock service response
		$this->profile_service->shouldReceive( 'update_profile' )
			->once()
			->with( $this->test_user_id, $update_data )
			->andReturn( $expected_profile );

		// Create request
		$request = new WP_REST_Request( 'POST', '/athlete-dashboard/v1/profile' );
		$request->set_body_params( $update_data );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		// Make request
		$response = $this->endpoint->handle_request( $request );

		// Assert response
		$this->assertEquals( 201, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );
		$this->assertEquals( $expected_profile, $response->get_data()['data']['profile'] );
	}

	/**
	 * Test update with invalid email.
	 */
	public function test_update_profile_invalid_email() {
		$update_data = array(
			'email' => 'invalid-email',
		);

		// Create request
		$request = new WP_REST_Request( 'POST', '/athlete-dashboard/v1/profile' );
		$request->set_body_params( $update_data );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		// Make request
		$response = $this->endpoint->handle_request( $request );

		// Assert response
		$this->assertEquals( 400, $response->get_status() );
		$this->assertFalse( $response->get_data()['success'] );
		$this->assertStringContainsString( 'Invalid email', $response->get_data()['message'] );
	}

	/**
	 * Test update with name too long.
	 */
	public function test_update_profile_name_too_long() {
		$update_data = array(
			'firstName' => str_repeat( 'a', 51 ), // 51 characters
		);

		// Create request
		$request = new WP_REST_Request( 'POST', '/athlete-dashboard/v1/profile' );
		$request->set_body_params( $update_data );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		// Make request
		$response = $this->endpoint->handle_request( $request );

		// Assert response
		$this->assertEquals( 400, $response->get_status() );
		$this->assertFalse( $response->get_data()['success'] );
		$this->assertStringContainsString( 'must not exceed 50 characters', $response->get_data()['message'] );
	}

	/**
	 * Test update with no data.
	 */
	public function test_update_profile_no_data() {
		// Create request
		$request = new WP_REST_Request( 'POST', '/athlete-dashboard/v1/profile' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		// Make request
		$response = $this->endpoint->handle_request( $request );

		// Assert response
		$this->assertEquals( 400, $response->get_status() );
		$this->assertFalse( $response->get_data()['success'] );
		$this->assertStringContainsString( 'No valid update data', $response->get_data()['message'] );
	}

	/**
	 * Test update with unauthorized user.
	 */
	public function test_update_profile_unauthorized() {
		// Set current user to 0 (not logged in)
		wp_set_current_user( 0 );

		$update_data = array(
			'firstName' => 'John',
		);

		// Create request
		$request = new WP_REST_Request( 'POST', '/athlete-dashboard/v1/profile' );
		$request->set_body_params( $update_data );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		// Make request
		$response = $this->endpoint->handle_request( $request );

		// Assert response
		$this->assertEquals( 401, $response->get_status() );
		$this->assertFalse( $response->get_data()['success'] );
		$this->assertStringContainsString( 'not logged in', $response->get_data()['message'] );
	}

	/**
	 * Test update with service error.
	 */
	public function test_update_profile_service_error() {
		$update_data = array(
			'firstName' => 'John',
		);

		// Mock service error
		$this->profile_service->shouldReceive( 'update_profile' )
			->once()
			->with( $this->test_user_id, $update_data )
			->andReturn( new WP_Error( 'update_failed', 'Failed to update profile', array( 'status' => 500 ) ) );

		// Create request
		$request = new WP_REST_Request( 'POST', '/athlete-dashboard/v1/profile' );
		$request->set_body_params( $update_data );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		// Make request
		$response = $this->endpoint->handle_request( $request );

		// Assert response
		$this->assertEquals( 500, $response->get_status() );
		$this->assertFalse( $response->get_data()['success'] );
		$this->assertStringContainsString( 'Failed to update profile', $response->get_data()['message'] );
	}
}
