<?php
/**
 * User Update Endpoint.
 *
 * @package AthleteDashboard\Features\Profile\API\Endpoints\User
 */

namespace AthleteDashboard\Features\Profile\API\Endpoints\User;

use AthleteDashboard\Features\Profile\API\Endpoints\Base\Base_Endpoint;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_User;

/**
 * Class User_Update
 *
 * @package AthleteDashboard\Features\Profile\API\Endpoints\User
 */
class User_Update extends Base_Endpoint {

	/**
	 * Get the endpoint route.
	 *
	 * @return string
	 */
	public function get_route(): string {
		return '/user';
	}

	/**
	 * Get the endpoint's HTTP method.
	 *
	 * @return string
	 */
	public function get_method(): string {
		return 'PUT';
	}

	/**
	 * Get the endpoint's permission callback.
	 *
	 * @return array
	 */
	public function get_permission_callback(): array {
		return array(
			'method' => 'check_logged_in',
		);
	}

	/**
	 * Handle the request.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_request( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$auth_result = $this->check_nonce( $request );
		if ( is_wp_error( $auth_result ) ) {
			return $auth_result;
		}

		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return $this->error( __( 'User not found.', 'athlete-dashboard' ), 404 );
		}

		$result = $this->process_user_updates( $user, $request );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $this->success(
			array(
				'message' => __( 'User updated successfully.', 'athlete-dashboard' ),
			)
		);
	}

	/**
	 * Process user updates.
	 *
	 * @param WP_User         $user    The user object.
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return true|WP_Error
	 */
	private function process_user_updates( WP_User $user, WP_REST_Request $request ): true|WP_Error {
		$user_data = array();

		// Process core user data updates.
		if ( $request->has_param( 'email' ) || $request->has_param( 'password' ) ) {
			$result = $this->validate_core_updates( $request );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$user_data = array_merge( $user_data, $result );
		}

		// Process meta updates.
		if ( $request->has_param( 'meta' ) ) {
			$result = $this->validate_meta_updates( $request );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$user_data['meta_input'] = $result;
		}

		// Update user.
		if ( ! empty( $user_data ) ) {
			$user_data['ID'] = $user->ID;
			$result          = wp_update_user( $user_data );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return true;
	}

	/**
	 * Validate core user data updates.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return array|WP_Error
	 */
	private function validate_core_updates( WP_REST_Request $request ): array|WP_Error {
		$user_data = array();

		if ( $request->has_param( 'email' ) ) {
			$email = sanitize_email( $request->get_param( 'email' ) );
			if ( ! is_email( $email ) ) {
				return $this->error( __( 'Invalid email address.', 'athlete-dashboard' ), 400 );
			}
			$user_data['user_email'] = $email;
		}

		if ( $request->has_param( 'password' ) ) {
			$result = $this->validate_password_update( $request );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$user_data['user_pass'] = $request->get_param( 'password' );
		}

		return $user_data;
	}

	/**
	 * Validate password update.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return true|WP_Error
	 */
	private function validate_password_update( WP_REST_Request $request ): true|WP_Error {
		$password = $request->get_param( 'password' );
		$confirm  = $request->get_param( 'passwordConfirmation' );
		$current  = $request->get_param( 'currentPassword' );

		if ( ! $current ) {
			return $this->error( __( 'Current password is required.', 'athlete-dashboard' ), 400 );
		}

		if ( ! wp_check_password( $current, wp_get_current_user()->user_pass, wp_get_current_user()->ID ) ) {
			return $this->error( __( 'Current password is incorrect.', 'athlete-dashboard' ), 400 );
		}

		if ( $password !== $confirm ) {
			return $this->error( __( 'Passwords do not match.', 'athlete-dashboard' ), 400 );
		}

		// translators: %1$s: field name.
		$min_length_message = __( 'Field %1$s must be at least 8 characters.', 'athlete-dashboard' );
		if ( 8 > strlen( $password ) ) {
			return $this->error( sprintf( $min_length_message, 'Password' ), 400 );
		}

		// translators: %1$s: field name.
		$max_length_message = __( 'Field %1$s must be at most 100 characters.', 'athlete-dashboard' );
		if ( 100 < strlen( $password ) ) {
			return $this->error( sprintf( $max_length_message, 'Password' ), 400 );
		}

		return true;
	}

	/**
	 * Validate meta updates.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return array|WP_Error
	 */
	private function validate_meta_updates( WP_REST_Request $request ): array|WP_Error {
		$meta = $request->get_param( 'meta' );
		if ( ! is_array( $meta ) ) {
			return $this->error( __( 'Meta must be an array.', 'athlete-dashboard' ), 400 );
		}

		$allowed_meta = array(
			'comment_shortcuts' => array(
				'type' => 'boolean',
			),
			'admin_color'       => array(
				'type' => 'string',
			),
			'rich_editing'      => array(
				'type' => 'boolean',
			),
		);

		$validated_meta = array();
		foreach ( $meta as $key => $value ) {
			if ( ! isset( $allowed_meta[ $key ] ) ) {
						continue;
			}

			if ( 'boolean' === $allowed_meta[ $key ]['type'] ) {
				$validated_meta[ $key ] = (bool) $value;
			} elseif ( 'string' === $allowed_meta[ $key ]['type'] ) {
				$validated_meta[ $key ] = sanitize_text_field( $value );
			}
		}

		return $validated_meta;
	}
}
