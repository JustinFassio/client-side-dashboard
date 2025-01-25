<?php
/**
 * Unit tests for User_Update endpoint.
 *
 * @package AthleteDashboard\Features\Profile\Tests\Unit\API\Endpoints
 */

namespace AthleteDashboard\Features\Profile\Tests\Unit\API\Endpoints;

use AthleteDashboard\Features\Profile\API\Endpoints\User\User_Update;
use AthleteDashboard\Features\Profile\Services\Profile_Service;
use AthleteDashboard\Features\Profile\API\Response_Factory;
use WP_REST_Request;
use WP_UnitTestCase;
use WP_Error;

/**
 * Class User_Update_Test
 */
class User_Update_Test extends WP_UnitTestCase {
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
	 * Test successful user update.
	 */
	public function test_update_user_success() {
		// Set up test data
		$update_data = array(
			'email' => 'newemail@example.com',
			'meta'  => array(
				'rich_editing'      => true,
				'comment_shortcuts' => false,
			),
		);

		// Create endpoint instance
		$endpoint = new User_Update( $this->service, $this->response_factory );

		// Create request
		$request = new WP_REST_Request( 'PUT', '/athlete-dashboard/v1/profile/user' );
		$request->set_body_params( $update_data );

		// Make request
		$response = $endpoint->handle_request( $request );
		$data     = $response->get_data();

		// Verify response
		$this->assertTrue( $data['success'] );
		$this->assertNull( $data['error'] );
		$this->assertEquals( 'User updated successfully.', $data['message'] );
	}

	/**
	 * Test unauthorized access.
	 */
	public function test_update_user_unauthorized() {
		// Set user to 0 (not logged in)
		wp_set_current_user( 0 );

		// Create endpoint instance
		$endpoint = new User_Update( $this->service, $this->response_factory );

		// Create request
		$request = new WP_REST_Request( 'PUT', '/athlete-dashboard/v1/profile/user' );
		$request->set_body_params( array( 'email' => 'test@example.com' ) );

		// Make request
		$response = $endpoint->handle_request( $request );
		$data     = $response->get_data();

		// Verify response
		$this->assertFalse( $data['success'] );
		$this->assertEquals( 401, $data['error']['status'] );
		$this->assertEquals( 'unauthorized', $data['error']['code'] );
	}

	/**
	 * Test invalid email update.
	 */
	public function test_update_user_invalid_email() {
		// Create endpoint instance
		$endpoint = new User_Update( $this->service, $this->response_factory );

		// Create request with invalid email
		$request = new WP_REST_Request( 'PUT', '/athlete-dashboard/v1/profile/user' );
		$request->set_body_params( array( 'email' => 'invalid-email' ) );

		// Make request
		$response = $endpoint->handle_request( $request );
		$data     = $response->get_data();

		// Verify response
		$this->assertFalse( $data['success'] );
		$this->assertEquals( 400, $data['error']['status'] );
		$this->assertEquals( 'validation_error', $data['error']['code'] );
	}

	/**
	 * Test password update validation.
	 */
	public function test_update_user_password_validation() {
		// Create endpoint instance
		$endpoint = new User_Update( $this->service, $this->response_factory );

		// Test cases for password validation
		$test_cases = array(
			'missing_current'    => array(
				'params'           => array(
					'password'             => 'newpassword123',
					'passwordConfirmation' => 'newpassword123',
				),
				'expected_message' => 'Current password is required.',
			),
			'password_mismatch'  => array(
				'params'           => array(
					'password'             => 'newpassword123',
					'passwordConfirmation' => 'different123',
					'currentPassword'      => 'currentpass',
				),
				'expected_message' => 'Passwords do not match.',
			),
			'password_too_short' => array(
				'params'           => array(
					'password'             => 'short',
					'passwordConfirmation' => 'short',
					'currentPassword'      => 'currentpass',
				),
				'expected_message' => 'Field Password must be at least 8 characters.',
			),
		);

		foreach ( $test_cases as $case => $data ) {
			$request = new WP_REST_Request( 'PUT', '/athlete-dashboard/v1/profile/user' );
			$request->set_body_params( $data['params'] );

			$response      = $endpoint->handle_request( $request );
			$response_data = $response->get_data();

			$this->assertFalse( $response_data['success'], "Failed case: $case" );
			$this->assertEquals( 400, $response_data['error']['status'], "Failed case: $case" );
			$this->assertEquals( $data['expected_message'], $response_data['error']['message'], "Failed case: $case" );
		}
	}

	/**
	 * Test meta updates validation.
	 */
	public function test_update_user_meta_validation() {
		// Create endpoint instance
		$endpoint = new User_Update( $this->service, $this->response_factory );

		// Test invalid meta format
		$request = new WP_REST_Request( 'PUT', '/athlete-dashboard/v1/profile/user' );
		$request->set_body_params(
			array(
				'meta' => 'not-an-array',
			)
		);

		$response = $endpoint->handle_request( $request );
		$data     = $response->get_data();

		$this->assertFalse( $data['success'] );
		$this->assertEquals( 400, $data['error']['status'] );
		$this->assertEquals( 'Meta must be an array.', $data['error']['message'] );

		// Test invalid meta field
		$request = new WP_REST_Request( 'PUT', '/athlete-dashboard/v1/profile/user' );
		$request->set_body_params(
			array(
				'meta' => array(
					'invalid_field' => 'value',
				),
			)
		);

		$response = $endpoint->handle_request( $request );
		$data     = $response->get_data();

		$this->assertTrue( $data['success'], 'Invalid meta fields should be ignored' );
	}

	/**
	 * Test password length boundary conditions.
	 */
	public function test_password_length_boundaries() {
		$endpoint = new User_Update( $this->service, $this->response_factory );

		// Test cases for password length boundaries
		$test_cases = array(
			'exactly_min_length' => array(
				'params'           => array(
					'password'             => str_repeat( 'a', 8 ),
					'passwordConfirmation' => str_repeat( 'a', 8 ),
					'currentPassword'      => 'currentpass',
				),
				'expected_success' => true,
			),
			'exactly_max_length' => array(
				'params'           => array(
					'password'             => str_repeat( 'a', 100 ),
					'passwordConfirmation' => str_repeat( 'a', 100 ),
					'currentPassword'      => 'currentpass',
				),
				'expected_success' => true,
			),
			'one_under_min'      => array(
				'params'           => array(
					'password'             => str_repeat( 'a', 7 ),
					'passwordConfirmation' => str_repeat( 'a', 7 ),
					'currentPassword'      => 'currentpass',
				),
				'expected_success' => false,
				'expected_message' => 'Field Password must be at least 8 characters.',
			),
			'one_over_max'       => array(
				'params'           => array(
					'password'             => str_repeat( 'a', 101 ),
					'passwordConfirmation' => str_repeat( 'a', 101 ),
					'currentPassword'      => 'currentpass',
				),
				'expected_success' => false,
				'expected_message' => 'Field Password must be at most 100 characters.',
			),
		);

		foreach ( $test_cases as $case => $data ) {
			$request = new WP_REST_Request( 'PUT', '/athlete-dashboard/v1/profile/user' );
			$request->set_body_params( $data['params'] );

			$response      = $endpoint->handle_request( $request );
			$response_data = $response->get_data();

			if ( $data['expected_success'] ) {
				$this->assertTrue( $response_data['success'], "Failed case: $case" );
			} else {
				$this->assertFalse( $response_data['success'], "Failed case: $case" );
				$this->assertEquals( 400, $response_data['error']['status'], "Failed case: $case" );
				$this->assertEquals( $data['expected_message'], $response_data['error']['message'], "Failed case: $case" );
			}
		}
	}

	/**
	 * Test comprehensive meta fields validation.
	 */
	public function test_comprehensive_meta_validation() {
		$endpoint = new User_Update( $this->service, $this->response_factory );

		$test_cases = array(
			'all_valid_fields'     => array(
				'meta'             => array(
					'comment_shortcuts' => true,
					'admin_color'       => 'fresh',
					'rich_editing'      => false,
				),
				'expected_success' => true,
			),
			'invalid_boolean_type' => array(
				'meta'             => array(
					'comment_shortcuts' => 'true', // string instead of boolean
					'rich_editing'      => 1, // number instead of boolean
				),
				'expected_success' => true, // should be converted to boolean
			),
			'mixed_valid_invalid'  => array(
				'meta'             => array(
					'comment_shortcuts' => true,
					'invalid_key'       => 'value',
					'rich_editing'      => false,
				),
				'expected_success' => true, // invalid key should be ignored
			),
			'nested_meta'          => array(
				'meta'             => array(
					'comment_shortcuts' => true,
					'nested'            => array(
						'key' => 'value',
					),
				),
				'expected_success' => true, // nested array should be ignored
			),
			'empty_meta_object'    => array(
				'meta'             => array(),
				'expected_success' => true,
			),
		);

		foreach ( $test_cases as $case => $data ) {
			$request = new WP_REST_Request( 'PUT', '/athlete-dashboard/v1/profile/user' );
			$request->set_body_params( array( 'meta' => $data['meta'] ) );

			$response      = $endpoint->handle_request( $request );
			$response_data = $response->get_data();

			$this->assertEquals(
				$data['expected_success'],
				$response_data['success'],
				"Failed case: $case"
			);
		}
	}

	/**
	 * Test edge cases and error handling.
	 */
	public function test_edge_cases() {
		$endpoint = new User_Update( $this->service, $this->response_factory );

		// Test empty payload
		$request  = new WP_REST_Request( 'PUT', '/athlete-dashboard/v1/profile/user' );
		$response = $endpoint->handle_request( $request );
		$this->assertTrue( $response->get_data()['success'], 'Empty payload should be accepted' );

		// Test unsupported HTTP methods
		$methods = array( 'GET', 'POST', 'DELETE' );
		foreach ( $methods as $method ) {
			$request  = new WP_REST_Request( $method, '/athlete-dashboard/v1/profile/user' );
			$response = $endpoint->handle_request( $request );
			$this->assertFalse( $response->get_data()['success'], "Method $method should be rejected" );
			$this->assertEquals( 405, $response->get_data()['error']['status'] );
		}

		// Test large payload
		$large_payload = array(
			'meta' => array(
				'comment_shortcuts' => true,
				'large_field'       => str_repeat( 'a', 1000000 ), // 1MB of data
			),
		);
		$request       = new WP_REST_Request( 'PUT', '/athlete-dashboard/v1/profile/user' );
		$request->set_body_params( $large_payload );
		$response = $endpoint->handle_request( $request );
		$this->assertTrue( $response->get_data()['success'], 'Large payload should be handled' );
	}

	/**
	 * Test simultaneous update of multiple fields.
	 */
	public function test_multiple_field_update() {
		$endpoint = new User_Update( $this->service, $this->response_factory );

		$update_data = array(
			'email'                => 'new@example.com',
			'password'             => 'newpassword123',
			'passwordConfirmation' => 'newpassword123',
			'currentPassword'      => 'currentpass',
			'meta'                 => array(
				'comment_shortcuts' => true,
				'rich_editing'      => false,
			),
		);

		$request = new WP_REST_Request( 'PUT', '/athlete-dashboard/v1/profile/user' );
		$request->set_body_params( $update_data );

		$response = $endpoint->handle_request( $request );
		$data     = $response->get_data();

		$this->assertTrue( $data['success'] );
		$this->assertNull( $data['error'] );
	}

	/**
	 * Clean up after test.
	 */
	public function tear_down() {
		parent::tear_down();

		// Clean up test user
		if ( $this->test_user_id ) {
			wp_delete_user( $this->test_user_id );
		}
	}
}
