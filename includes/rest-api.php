<?php
/**
 * Custom REST API endpoints for the Athlete Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register custom REST API endpoints
 */
function athlete_dashboard_register_rest_routes() {
	register_rest_route(
		'custom/v1',
		'/profile',
		array(
			'methods'             => 'GET',
			'callback'            => 'athlete_dashboard_get_current_user_profile',
			'permission_callback' => function () {
				return is_user_logged_in();
			},
		)
	);
}
add_action( 'rest_api_init', 'athlete_dashboard_register_rest_routes' );

/**
 * Get current user profile data
 *
 * @return array|WP_Error User profile data or error
 */
function athlete_dashboard_get_current_user_profile() {
	$current_user = wp_get_current_user();

	if ( ! $current_user || ! $current_user->ID ) {
		return new WP_Error(
			'no_user',
			'No user found',
			array( 'status' => 404 )
		);
	}

	// Get additional user meta if needed
	$user_meta = get_user_meta( $current_user->ID );

	return array(
		'id'        => $current_user->ID,
		'name'      => $current_user->display_name,
		'email'     => $current_user->user_email,
		'roles'     => $current_user->roles,
		// Add any additional user meta fields needed by the dashboard
		'firstName' => $user_meta['first_name'][0] ?? '',
		'lastName'  => $user_meta['last_name'][0] ?? '',
	);
}
