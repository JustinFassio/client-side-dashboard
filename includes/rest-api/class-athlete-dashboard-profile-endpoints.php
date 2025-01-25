<?php
/**
 * Legacy Profile Endpoints
 *
 * @deprecated 1.0.0 Use AthleteProfile\API\ProfileEndpoints instead
 * @see AthleteProfile\API\ProfileEndpoints
 */

namespace AthleteDashboard\Api;

use AthleteDashboard\Core\Config\Debug;

class Athlete_Dashboard_Profile_Endpoints {
	/**
	 * Initialize the endpoints.
	 *
	 * @deprecated 1.0.0 Use AthleteProfile\API\ProfileEndpoints::init() instead
	 */
	public function __construct() {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			Debug::log( '[Deprecated] Legacy profile endpoints initialized', 'api' );
		}
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @deprecated 1.0.0 Use AthleteProfile\API\ProfileEndpoints::register_routes() instead
	 */
	public function register_routes() {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			Debug::log( '[Deprecated] Registering legacy profile routes', 'api' );
		}
		register_rest_route(
			'athlete-dashboard/v1',
			'/profile',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_profile' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	/**
	 * Check if user has permission.
	 *
	 * @deprecated 1.0.0 Use AthleteProfile\API\ProfileEndpoints::check_auth() instead
	 */
	public function check_permission() {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			Debug::log( '[Deprecated] Legacy profile permission check called', 'api' );
		}
		return is_user_logged_in();
	}

	/**
	 * Get profile data.
	 *
	 * @deprecated 1.0.0 Use AthleteProfile\API\ProfileEndpoints::get_profile() instead
	 */
	public function get_profile( $request ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			Debug::log( '[Deprecated] Legacy get_profile endpoint called', 'api' );
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				Debug::log( '[Deprecated] Legacy get_profile - no user found', 'api' );
			}
			return new \WP_Error(
				'no_user',
				'User not found',
				array( 'status' => 404 )
			);
		}

		$user = get_userdata( $user_id );
		$meta = get_user_meta( $user_id );

		$response = array(
			'id'          => $user_id,
			'username'    => $user->user_login,
			'email'       => $user->user_email,
			'displayName' => $user->display_name,
			'firstName'   => $meta['first_name'][0] ?? '',
			'lastName'    => $meta['last_name'][0] ?? '',
		);

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			Debug::log( '[Deprecated] Legacy get_profile response: ' . json_encode( $response ), 'api' );
		}

		return $response;
	}
}

// Initialize the endpoints
// @deprecated 1.0.0 This initialization will be removed in future versions
new Athlete_Dashboard_Profile_Endpoints();
