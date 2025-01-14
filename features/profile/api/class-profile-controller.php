<?php
/**
 * Profile controller class.
 *
 * @package AthleteDashboard\Features\Profile\API
 */

namespace AthleteDashboard\Features\Profile\API;

use AthleteDashboard\Features\Profile\Services\Profile_Service;
use AthleteDashboard\Features\Profile\Services\User_Service;
use AthleteDashboard\Features\Profile\Services\Combined_Service;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class for handling profile REST API requests.
 */
class Profile_Controller {
	/**
	 * Profile service instance.
	 *
	 * @var Profile_Service
	 */
	private $profile_service;

	/**
	 * User service instance.
	 *
	 * @var User_Service
	 */
	private $user_service;

	/**
	 * Combined service instance.
	 *
	 * @var Combined_Service
	 */
	private $combined_service;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->profile_service  = new Profile_Service();
		$this->user_service     = new User_Service();
		$this->combined_service = new Combined_Service();
	}

	/**
	 * Check if user is authorized.
	 *
	 * @return bool|WP_Error True if authorized, error otherwise.
	 */
	private function check_auth() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden',
				'You must be logged in to access this endpoint.',
				array( 'status' => 401 )
			);
		}
		return true;
	}

	/**
	 * Get profile data.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_profile( WP_REST_Request $request ) {
		$auth_check = $this->check_auth();
		if ( is_wp_error( $auth_check ) ) {
			return $auth_check;
		}

		$user_id = get_current_user_id();
		$data    = $this->profile_service->get_profile( $user_id );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			)
		);
	}

	/**
	 * Update profile data.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function update_profile( WP_REST_Request $request ) {
		$auth_check = $this->check_auth();
		if ( is_wp_error( $auth_check ) ) {
			return $auth_check;
		}

		$user_id = get_current_user_id();
		$data    = $request->get_json_params();
		$result  = $this->profile_service->update_profile( $user_id, $data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Profile updated successfully.',
			)
		);
	}

	/**
	 * Get combined profile and user data.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_combined_data( WP_REST_Request $request ) {
		$auth_check = $this->check_auth();
		if ( is_wp_error( $auth_check ) ) {
			return $auth_check;
		}

		$user_id = get_current_user_id();
		$data    = $this->combined_service->get_combined_data( $user_id );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			)
		);
	}
}
