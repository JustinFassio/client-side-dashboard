<?php
/**
 * Custom REST API endpoints for the Athlete Dashboard
 *
 * @deprecated 1.0.0 Legacy REST API endpoints. Use AthleteProfile\API\ProfileEndpoints instead.
 * @see AthleteProfile\API\ProfileEndpoints
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AthleteDashboard\Core\Config\Debug;

/**
 * Register custom REST API endpoints
 *
 * @deprecated 1.0.0 Use AthleteProfile\API\ProfileEndpoints::register_routes() instead
 */
function athlete_dashboard_register_rest_routes() {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		Debug::log( '[Deprecated] Legacy REST API routes registration called', 'api' );
		trigger_error(
			'Function athlete_dashboard_register_rest_routes is deprecated. Use AthleteProfile\API\ProfileEndpoints::register_routes() instead.',
			E_USER_DEPRECATED
		);
	}

	register_rest_route(
		'custom/v1',
		'/profile',
		array(
			'methods'             => 'GET',
			'callback'            => 'athlete_dashboard_get_current_user_profile',
			'permission_callback' => function () {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					Debug::log( '[Deprecated] Legacy permission callback called', 'api' );
				}
				return is_user_logged_in();
			},
		)
	);
}
add_action( 'rest_api_init', 'athlete_dashboard_register_rest_routes' );

/**
 * Get current user profile data
 *
 * @deprecated 1.0.0 Use AthleteProfile\API\ProfileEndpoints::get_profile() instead
 * @return array|WP_Error User profile data or error
 */
function athlete_dashboard_get_current_user_profile() {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		Debug::log( '[Deprecated] Legacy get_current_user_profile called', 'api' );
		trigger_error(
			'Function athlete_dashboard_get_current_user_profile is deprecated. Use AthleteProfile\API\ProfileEndpoints::get_profile() instead.',
			E_USER_DEPRECATED
		);
	}

	$current_user = wp_get_current_user();

	if ( ! $current_user || ! $current_user->ID ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			Debug::log( '[Deprecated] Legacy get_current_user_profile - no user found', 'api' );
		}
		return new WP_Error(
			'no_user',
			'No user found',
			array( 'status' => 404 )
		);
	}

	// Get additional user meta if needed
	$user_meta = get_user_meta( $current_user->ID );

	$response = array(
		'id'        => $current_user->ID,
		'name'      => $current_user->display_name,
		'email'     => $current_user->user_email,
		'roles'     => $current_user->roles,
		// Add any additional user meta fields needed by the dashboard
		'firstName' => $user_meta['first_name'][0] ?? '',
		'lastName'  => $user_meta['last_name'][0] ?? '',
	);

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		Debug::log( '[Deprecated] Legacy get_current_user_profile response: ' . json_encode( $response ), 'api' );
	}

	return $response;
}
