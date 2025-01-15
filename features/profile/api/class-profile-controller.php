<?php
/**
 * Profile REST API Controller.
 *
 * Handles REST API endpoints for managing athlete profiles. This class follows
 * WordPress coding standards and best practices for REST API controllers.
 *
 * @package AthleteDashboard\Features\Profile\API
 * @since 1.0.0
 */

namespace AthleteDashboard\Features\Profile\API;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use AthleteDashboard\Core\Config\Debug;
use AthleteDashboard\Features\Profile\Services\Profile_Service;

/**
 * Profile Controller class.
 *
 * @since 1.0.0
 */
class Profile_Controller extends WP_REST_Controller {
	/**
	 * Profile service instance.
	 *
	 * @var Profile_Service
	 */
	private Profile_Service $service;

	/**
	 * Constructor.
	 *
	 * @param Profile_Service $service Profile service instance.
	 */
	public function __construct( Profile_Service $service ) {
		$this->namespace = 'athlete-dashboard/v1';
		$this->rest_base = 'profile';
		$this->service   = $service;
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes(): void {
		Debug::log( 'Registering profile routes', 'rest.profile' );

		// Get profile endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_profile' ),
					'permission_callback' => array( $this, 'check_auth' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_profile' ),
					'permission_callback' => array( $this, 'check_auth' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
			)
		);

		// Get basic profile data endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/basic',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_basic_data' ),
				'permission_callback' => array( $this, 'check_auth' ),
			)
		);

		Debug::log( 'Profile routes registered', 'rest.profile' );
	}

	/**
	 * Check if the current user is authorized.
	 *
	 * @return bool|WP_Error True if authorized, WP_Error if not.
	 */
	public function check_auth() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in to access profile data.', 'athlete-dashboard' ),
				array( 'status' => 401 )
			);
		}
		return true;
	}

	/**
	 * Get the current user's profile.
	 *
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_profile() {
		$user_id = get_current_user_id();
		$result  = $this->service->get_profile( $user_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Update the current user's profile.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function update_profile( WP_REST_Request $request ) {
		$user_id = get_current_user_id();
		$data    = $request->get_json_params();

		if ( empty( $data ) ) {
			return new WP_Error(
				'rest_invalid_data',
				__( 'No profile data provided.', 'athlete-dashboard' ),
				array( 'status' => 400 )
			);
		}

		$result = $this->service->update_profile( $user_id, $data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Get basic profile data.
	 *
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_basic_data() {
		$user_id = get_current_user_id();
		$result  = $this->service->get_basic_data( $user_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Get the schema for profile data.
	 *
	 * @return array Schema data.
	 */
	public function get_item_schema(): array {
		if ( $this->schema ) {
			return $this->schema;
		}

		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'profile',
			'type'       => 'object',
			'properties' => array(
				'email'          => array(
					'type'        => 'string',
					'format'      => 'email',
					'description' => __( 'User email address.', 'athlete-dashboard' ),
				),
				'units'          => array(
					'type'        => 'string',
					'enum'        => array( 'metric', 'imperial' ),
					'description' => __( 'Preferred measurement units.', 'athlete-dashboard' ),
				),
				'height'         => array(
					'type'        => 'number',
					'description' => __( 'User height in preferred units.', 'athlete-dashboard' ),
				),
				'weight'         => array(
					'type'        => 'number',
					'description' => __( 'User weight in preferred units.', 'athlete-dashboard' ),
				),
				'age'            => array(
					'type'        => 'integer',
					'description' => __( 'User age.', 'athlete-dashboard' ),
				),
				'fitness_level'  => array(
					'type'        => 'string',
					'enum'        => array( 'beginner', 'intermediate', 'advanced', 'expert' ),
					'description' => __( 'User fitness level.', 'athlete-dashboard' ),
				),
				'activity_level' => array(
					'type'        => 'string',
					'enum'        => array( 'sedentary', 'light', 'moderate', 'very_active', 'extra_active' ),
					'description' => __( 'User activity level.', 'athlete-dashboard' ),
				),
			),
		);

		return $this->schema;
	}
}
