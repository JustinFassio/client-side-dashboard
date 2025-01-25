<?php
/**
 * User Get Endpoint.
 *
 * @package AthleteDashboard\Features\Profile\API\Endpoints\User
 */

namespace AthleteDashboard\Features\Profile\API\Endpoints\User;

use AthleteDashboard\Features\Profile\API\Endpoints\Base\Base_Endpoint;
use AthleteDashboard\Features\Profile\API\Response_Factory;
use AthleteDashboard\Features\Profile\Services\Profile_Service;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Class User_Get
 *
 * Handles GET requests for user profile data.
 */
class User_Get extends Base_Endpoint {

	/**
	 * Profile service instance.
	 *
	 * @var Profile_Service
	 */
	protected Profile_Service $service;

	/**
	 * Response factory instance.
	 *
	 * @var Response_Factory
	 */
	protected Response_Factory $response_factory;

	/**
	 * Constructor.
	 *
	 * @param Profile_Service  $service         Profile service instance.
	 * @param Response_Factory $response_factory Response factory instance.
	 */
	public function __construct( Profile_Service $service, Response_Factory $response_factory ) {
		parent::__construct( $service, $response_factory );
		error_log( '🔧 DEBUG: User_Get constructor called' );
		error_log( '🔧 DEBUG: Namespace set to: ' . $this->namespace );
		error_log( '🔧 DEBUG: Rest base set to: ' . $this->rest_base );
	}

	/**
	 * Get the route for this endpoint.
	 *
	 * @return string
	 */
	public function get_route(): string {
		error_log( '🎯 DEBUG: User_Get::get_route() called' );
		return 'user/(?P<user_id>\d+)';
	}

	/**
	 * Get the endpoint's HTTP method.
	 *
	 * @return string HTTP method.
	 */
	public function get_method(): string {
		return WP_REST_Server::READABLE;
	}

	/**
	 * Check if the current user has permission to access this endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if permission granted, WP_Error if denied.
	 */
	public function check_permission( WP_REST_Request $request ): bool|WP_Error {
		error_log( '🔒 DEBUG: User_Get::check_permission() called' );

		// First check if user is logged in
		if ( ! is_user_logged_in() ) {
			error_log( '❌ DEBUG: Permission denied - User not logged in' );
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in to access this endpoint.', 'athlete-dashboard' ),
				array( 'status' => 401 )
			);
		}

		$current_user_id   = get_current_user_id();
		$requested_user_id = (int) $request['user_id'];

		error_log( "🔍 DEBUG: Checking permission - Current user: $current_user_id, Requested user: $requested_user_id" );

		// Allow access if:
		// 1. User is requesting their own profile
		// 2. User has capability to edit other users
		if ( $current_user_id === $requested_user_id || current_user_can( 'edit_users' ) ) {
			error_log( '✅ DEBUG: Permission granted - Own profile or has edit_users capability' );
			return true;
		}

		error_log( '❌ DEBUG: Permission denied - Cannot access requested profile' );
		return new WP_Error(
			'rest_forbidden',
			__( 'You do not have permission to view this profile.', 'athlete-dashboard' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Handle the GET request for user profile data.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function handle_request( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		error_log( '🎯 DEBUG: User_Get::handle_request called' );

		try {
			$user_id = (int) $request['user_id'];
			error_log( "🔍 DEBUG: Fetching profile for user $user_id" );

			// Get profile data from service
			$profile = $this->service->get_profile( $user_id );

			if ( is_wp_error( $profile ) ) {
				error_log( '❌ DEBUG: Service returned error - ' . $profile->get_error_message() );
				return $this->response_factory->error(
					'DISTINCTIVE ERROR: Failed to get profile from service: ' . $profile->get_error_message(),
					$profile->get_error_data()['status'] ?? 500,
					array( 'error_details' => 'This error is from User_Get endpoint' )
				);
			}

			error_log( '✅ DEBUG: Successfully retrieved profile data' );
			return $this->response_factory->success(
				array(
					'id'      => $user_id,
					'profile' => $profile,
				)
			);

		} catch ( \Exception $e ) {
			error_log( '❌ DEBUG: Exception in handle_request - ' . $e->getMessage() );
			return $this->response_factory->error(
				'DISTINCTIVE ERROR: Exception in User_Get endpoint',
				500,
				array( 'error' => $e->getMessage() )
			);
		}
	}

	/**
	 * Get the schema for the endpoint response.
	 *
	 * @return array
	 */
	public function get_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'profile',
			'type'       => 'object',
			'properties' => array(
				'profile' => array(
					'type'       => 'object',
					'properties' => array(
						'id'                    => array(
							'type'        => 'integer',
							'description' => __( 'User ID.', 'athlete-dashboard' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'firstName'             => array(
							'type'        => 'string',
							'description' => __( 'User\'s first name.', 'athlete-dashboard' ),
							'context'     => array( 'view', 'edit' ),
						),
						'lastName'              => array(
							'type'        => 'string',
							'description' => __( 'User\'s last name.', 'athlete-dashboard' ),
							'context'     => array( 'view', 'edit' ),
						),
						'email'                 => array(
							'type'        => 'string',
							'format'      => 'email',
							'description' => __( 'User\'s email address.', 'athlete-dashboard' ),
							'context'     => array( 'view', 'edit' ),
						),
						'age'                   => array(
							'type'        => 'integer',
							'description' => __( 'User\'s age.', 'athlete-dashboard' ),
							'context'     => array( 'view', 'edit' ),
						),
						'gender'                => array(
							'type'        => 'string',
							'description' => __( 'User\'s gender.', 'athlete-dashboard' ),
							'context'     => array( 'view', 'edit' ),
						),
						'height'                => array(
							'type'        => 'number',
							'description' => __( 'User\'s height in cm.', 'athlete-dashboard' ),
							'context'     => array( 'view', 'edit' ),
						),
						'weight'                => array(
							'type'        => 'number',
							'description' => __( 'User\'s weight in kg.', 'athlete-dashboard' ),
							'context'     => array( 'view', 'edit' ),
						),
						'fitnessGoals'          => array(
							'type'        => 'array',
							'items'       => array(
								'type' => 'string',
							),
							'description' => __( 'User\'s fitness goals.', 'athlete-dashboard' ),
							'context'     => array( 'view', 'edit' ),
						),
						'preferredWorkoutTypes' => array(
							'type'        => 'array',
							'items'       => array(
								'type' => 'string',
							),
							'description' => __( 'User\'s preferred workout types.', 'athlete-dashboard' ),
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
			),
		);
	}
}
