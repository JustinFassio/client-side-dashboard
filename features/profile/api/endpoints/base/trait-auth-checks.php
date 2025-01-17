<?php
/**
 * Auth Checks Trait.
 *
 * @package AthleteDashboard\Features\Profile\API\Endpoints\Base
 */

namespace AthleteDashboard\Features\Profile\API\Endpoints\Base;

use WP_Error;

/**
 * Trait for handling endpoint authorization checks.
 */
trait Auth_Checks {
	/**
	 * Check if user is logged in.
	 *
	 * @return bool|WP_Error True if logged in, WP_Error if not.
	 */
	protected function check_logged_in(): bool|WP_Error {
		error_log( '=== Auth_Checks::check_logged_in ===' );
		error_log( 'Current User ID: ' . get_current_user_id() );
		error_log( 'Is User Logged In: ' . ( is_user_logged_in() ? 'Yes' : 'No' ) );
		error_log( 'Current User: ' . wp_json_encode( wp_get_current_user() ) );

		if ( ! is_user_logged_in() ) {
			error_log( 'User is not logged in' );
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in to access this endpoint.', 'athlete-dashboard' ),
				array( 'status' => 401 )
			);
		}
		error_log( 'User is logged in' );
		return true;
	}

	/**
	 * Check if user has specific capability.
	 *
	 * @param string $capability The capability to check.
	 * @return bool|WP_Error True if has capability, WP_Error if not.
	 */
	protected function check_capability( string $capability ): bool|WP_Error {
		if ( ! current_user_can( $capability ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to access this endpoint.', 'athlete-dashboard' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Check if user owns the resource.
	 *
	 * @param int $resource_user_id The user ID that owns the resource.
	 * @return bool|WP_Error True if owns resource, WP_Error if not.
	 */
	protected function check_resource_owner( int $resource_user_id ): bool|WP_Error {
		error_log( '=== Auth_Checks::check_resource_owner ===' );
		error_log( 'Current User ID: ' . get_current_user_id() );
		error_log( 'Resource User ID: ' . $resource_user_id );

		if ( get_current_user_id() !== $resource_user_id ) {
			error_log( 'Resource owner check failed - user does not own resource' );
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to access this resource.', 'athlete-dashboard' ),
				array( 'status' => 403 )
			);
		}
		error_log( 'Resource owner check passed' );
		return true;
	}

	/**
	 * Verify nonce for the request.
	 *
	 * @param string $nonce The nonce to verify.
	 * @param string $action The nonce action.
	 * @return bool|WP_Error True if nonce is valid, WP_Error if not.
	 */
	protected function verify_nonce( string $nonce, string $action ): bool|WP_Error {
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			return new WP_Error(
				'rest_invalid_nonce',
				__( 'Invalid security token.', 'athlete-dashboard' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Run multiple auth checks.
	 *
	 * @param array $checks Array of check methods and their args.
	 * @return bool|WP_Error True if all pass, WP_Error on first failure.
	 */
	protected function run_auth_checks( array $checks ): bool|WP_Error {
		foreach ( $checks as $check ) {
			$method = $check['method'];
			$args   = $check['args'] ?? array();

			if ( ! method_exists( $this, $method ) ) {
				continue;
			}

			$result = call_user_func_array( array( $this, $method ), $args );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return true;
	}
}
