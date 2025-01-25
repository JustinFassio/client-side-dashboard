<?php
/**
 * Integration tests for User_Update endpoint.
 *
 * @package AthleteDashboard\Features\Profile\Tests\Integration\API\Endpoints
 */

namespace AthleteDashboard\Features\Profile\Tests\Integration\API\Endpoints;

use AthleteDashboard\Features\Profile\API\Endpoints\User\User_Update;
use AthleteDashboard\Features\Profile\Services\Profile_Service;
use AthleteDashboard\Features\Profile\API\Response_Factory;
use WP_REST_Request;
use WP_UnitTestCase;
use AthleteDashboard\Features\Profile\Repository\Profile_Repository;
use AthleteDashboard\Features\Profile\Validation\Profile_Validator;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class User_Update_Integration_Test
 */
class User_Update_Integration_Test extends WP_UnitTestCase {
	/**
	 * Profile service instance.
	 *
	 * @var Profile_Service
	 */
	private $profile_service;

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

		// Create test user with initial profile data
		$this->test_user_id = $this->factory->user->create(
			array(
				'role'         => 'subscriber',
				'user_login'   => 'testuser',
				'user_email'   => 'test@example.com',
				'display_name' => 'Test User',
			)
		);

		// Set up initial profile data
		$initial_profile = array(
			'age'             => 30,
			'gender'          => 'male',
			'heightCm'        => 180,
			'weightKg'        => 80,
			'activityLevel'   => 'moderate',
			'experienceLevel' => 'intermediate',
			'equipment'       => array( 'dumbbell', 'barbell' ),
			'fitnessGoals'    => array( 'strength', 'endurance' ),
		);
		update_user_meta( $this->test_user_id, '_athlete_profile_data', $initial_profile );

		// Initialize services with real implementations and dependencies
		$repository             = new Profile_Repository();
		$validator              = new Profile_Validator();
		$this->profile_service  = new Profile_Service( $repository, $validator );
		$this->response_factory = new Response_Factory();

		// Set current user
		wp_set_current_user( $this->test_user_id );
	}

	/**
	 * Test profile service integration with user updates.
	 */
	public function test_profile_service_integration() {
		// Create endpoint instance with real Profile Service
		$endpoint = new User_Update( $this->profile_service, $this->response_factory );

		// Update user data
		$update_data = array(
			'email' => 'updated@example.com',
			'meta'  => array(
				'rich_editing'      => true,
				'comment_shortcuts' => false,
			),
		);

		$request = new WP_REST_Request( 'PUT', '/athlete-dashboard/v1/profile/user' );
		$request->set_body_params( $update_data );

		// Make the update request
		$response = $endpoint->handle_request( $request );
		$this->assertTrue( $response->get_data()['success'] );

		// Verify profile service has updated data
		$profile_data = $this->profile_service->get_profile( $this->test_user_id );
		$this->assertNotInstanceOf( 'WP_Error', $profile_data );
		$this->assertEquals( 'updated@example.com', $profile_data['email'] );

		// Verify WordPress user meta is updated
		$user_meta = get_user_meta( $this->test_user_id );
		$this->assertEquals( '1', $user_meta['rich_editing'][0] );
		$this->assertEquals( '0', $user_meta['comment_shortcuts'][0] );
	}

	/**
	 * Test data consistency across features.
	 */
	public function test_data_consistency() {
		// Set current user
		wp_set_current_user( $this->test_user_id );

		$endpoint = new User_Update( $this->profile_service, $this->response_factory );

		// Update user data
		$update_data = array(
			'meta' => array(
				'workout_preferences' => array(
					'experienceLevel' => 'beginner',
					'equipment'       => array( 'bodyweight' ),
				),
			),
		);

		$request = new WP_REST_Request( 'PUT', '/athlete-dashboard/v1/profile/user' );
		$request->set_body_params( $update_data );

		// Make the update request
		$response = $endpoint->handle_request( $request );

		// Verify update was successful
		$this->assertTrue( $response->get_data()['success'] );

		// Verify profile data was updated correctly
		$profile_data = $this->profile_service->get_profile( $this->test_user_id );
		$this->assertEquals( 'beginner', $profile_data['experienceLevel'] );
		$this->assertEquals( array( 'bodyweight' ), $profile_data['equipment'] );
	}

	/**
	 * Test error handling for invalid email updates.
	 */
	public function test_invalid_email_update() {
		// Set current user
		wp_set_current_user( $this->test_user_id );

		$endpoint = new User_Update( $this->profile_service, $this->response_factory );

		// Try to update with invalid email
		$update_data = array(
			'email' => 'not-a-valid-email',
		);

		$request = new WP_REST_Request( 'PUT', '/athlete-dashboard/v1/profile/user' );
		$request->set_body_params( $update_data );

		// Make the update request
		$response = $endpoint->handle_request( $request );

		// Verify error response
		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertEquals( 400, $response->get_error_data()['status'] );
		$this->assertEquals( 'Invalid email address.', $response->get_error_message() );

		// Verify original email remains unchanged
		$profile_data = $this->profile_service->get_profile( $this->test_user_id );
		$this->assertEquals( 'test@example.com', $profile_data['email'] );
	}

	/**
	 * Test error handling for invalid workout preferences.
	 */
	public function test_invalid_workout_preferences() {
		// Set current user
		wp_set_current_user( $this->test_user_id );

		$endpoint = new User_Update( $this->profile_service, $this->response_factory );

		// Try to update with invalid experience level
		$update_data = array(
			'meta' => array(
				'workout_preferences' => array(
					'experienceLevel' => 'invalid_level',
					'equipment'       => array( 'bodyweight' ),
				),
			),
		);

		$request = new WP_REST_Request( 'PUT', '/athlete-dashboard/v1/profile/user' );
		$request->set_body_params( $update_data );

		// Make the update request
		$response = $endpoint->handle_request( $request );

		// Verify error response
		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertEquals( 400, $response->get_error_data()['status'] );

		// Verify original experience level remains unchanged
		$profile_data = $this->profile_service->get_profile( $this->test_user_id );
		$this->assertEquals( 'intermediate', $profile_data['experienceLevel'] );
	}

	/**
	 * Test concurrent updates to ensure data integrity.
	 */
	public function test_concurrent_updates() {
		// Set current user
		wp_set_current_user( $this->test_user_id );

		$endpoint = new User_Update( $this->profile_service, $this->response_factory );

		// First update
		$first_update = array(
			'meta' => array(
				'workout_preferences' => array(
					'experienceLevel' => 'beginner',
				),
			),
		);

		$first_request = new WP_REST_Request( 'PUT', '/athlete-dashboard/v1/profile/user' );
		$first_request->set_body_params( $first_update );

		// Second update
		$second_update = array(
			'meta' => array(
				'workout_preferences' => array(
					'experienceLevel' => 'advanced',
				),
			),
		);

		$second_request = new WP_REST_Request( 'PUT', '/athlete-dashboard/v1/profile/user' );
		$second_request->set_body_params( $second_update );

		// Execute both updates in quick succession
		$first_response  = $endpoint->handle_request( $first_request );
		$second_response = $endpoint->handle_request( $second_request );

		// Verify both updates were successful
		$this->assertTrue( $first_response->get_data()['success'] );
		$this->assertTrue( $second_response->get_data()['success'] );

		// Verify final state reflects the second update
		$profile_data = $this->profile_service->get_profile( $this->test_user_id );
		$this->assertEquals( 'advanced', $profile_data['experienceLevel'] );
	}

	/**
	 * Clean up after test.
	 */
	public function tear_down() {
		parent::tear_down();

		// Clean up test user and associated data
		if ( $this->test_user_id ) {
			delete_user_meta( $this->test_user_id, '_athlete_profile_data' );
			wp_delete_user( $this->test_user_id );
		}
	}
}
