<?php
/**
 * Endpoint Verifier Helper.
 *
 * @package AthleteDashboard\Features\Profile\API\Migration
 */

namespace AthleteDashboard\Features\Profile\API\Migration;

use WP_REST_Request;
use WP_Error;
use AthleteDashboard\Features\Profile\API\Profile_Routes;

/**
 * Class Endpoint_Verifier
 *
 * Simple helper to verify endpoint responses during migration.
 */
class Endpoint_Verifier {
	/**
	 * Profile routes instance.
	 *
	 * @var Profile_Routes
	 */
	private Profile_Routes $routes;

	/**
	 * Constructor.
	 *
	 * @param Profile_Routes $routes Profile routes instance.
	 */
	public function __construct( Profile_Routes $routes ) {
		$this->routes = $routes;
	}

	/**
	 * Compare responses between old and new endpoints.
	 *
	 * @param int $user_id User ID to test.
	 * @return array Comparison results.
	 */
	public function compare_responses( int $user_id ): array {
		// Get response from old endpoint
		$old_response = $this->get_legacy_response( $user_id );

		// Get response from new endpoint
		$new_response = $this->get_new_response( $user_id );

		return array(
			'match'       => $this->responses_match( $old_response, $new_response ),
			'old'         => $old_response,
			'new'         => $new_response,
			'differences' => $this->find_differences( $old_response, $new_response ),
		);
	}

	/**
	 * Get response using legacy implementation.
	 *
	 * @param int $user_id User ID.
	 * @return array|WP_Error Response data or error.
	 */
	private function get_legacy_response( int $user_id ): array|WP_Error {
		// Temporarily disable new endpoint
		$this->routes->toggle_new_user_get( false );

		// Create and send request
		$request = new WP_REST_Request( 'GET', '/athlete-dashboard/v1/profile/user' );
		$request->set_param( 'user_id', $user_id );
		$response = rest_do_request( $request );

		return $response->get_data();
	}

	/**
	 * Get response using new implementation.
	 *
	 * @param int $user_id User ID.
	 * @return array|WP_Error Response data or error.
	 */
	private function get_new_response( int $user_id ): array|WP_Error {
		// Enable new endpoint
		$this->routes->toggle_new_user_get( true );

		// Create and send request
		$request = new WP_REST_Request( 'GET', '/athlete-dashboard/v1/profile/user' );
		$request->set_param( 'user_id', $user_id );
		$response = rest_do_request( $request );

		return $response->get_data();
	}

	/**
	 * Check if responses match.
	 *
	 * @param array|WP_Error $old_response Old response.
	 * @param array|WP_Error $new_response New response.
	 * @return bool True if responses match.
	 */
	private function responses_match( array|WP_Error $old_response, array|WP_Error $new_response ): bool {
		// If both are errors, compare all fields
		if ( is_array( $old_response ) && isset( $old_response['code'] ) &&
			is_array( $new_response ) && isset( $new_response['code'] ) ) {
			return $old_response['code'] === $new_response['code'] &&
					$old_response['message'] === $new_response['message'] &&
					$old_response['data']['status'] === $new_response['data']['status'];
		}

		// If both are regular responses, compare essential fields
		if ( is_array( $old_response ) && is_array( $new_response ) ) {
			$fields = array( 'id', 'email', 'username', 'display_name' );

			foreach ( $fields as $field ) {
				if ( ! isset( $old_response[ $field ] ) || ! isset( $new_response[ $field ] ) ||
					$old_response[ $field ] !== $new_response[ $field ] ) {
					return false;
				}
			}

			return true;
		}

		// If one is an error and the other isn't, they don't match
		return false;
	}

	/**
	 * Find differences between responses.
	 *
	 * @param array|WP_Error $old_response Old response.
	 * @param array|WP_Error $new_response New response.
	 * @return array Array of differences.
	 */
	private function find_differences( array|WP_Error $old_response, array|WP_Error $new_response ): array {
		$differences = array();

		if ( is_wp_error( $old_response ) && is_wp_error( $new_response ) ) {
			if ( $old_response->get_error_code() !== $new_response->get_error_code() ) {
				$differences['error_code'] = array(
					'old' => $old_response->get_error_code(),
					'new' => $new_response->get_error_code(),
				);
			}
			return $differences;
		}

		if ( is_wp_error( $old_response ) ) {
			return array( 'old_response' => 'WP_Error: ' . $old_response->get_error_message() );
		}

		if ( is_wp_error( $new_response ) ) {
			return array( 'new_response' => 'WP_Error: ' . $new_response->get_error_message() );
		}

		// Compare all fields and note differences
		foreach ( $old_response as $key => $value ) {
			if ( ! isset( $new_response[ $key ] ) ) {
				$differences[ $key ] = array( 'missing_in_new' => $value );
				continue;
			}

			if ( $value !== $new_response[ $key ] ) {
				$differences[ $key ] = array(
					'old' => $value,
					'new' => $new_response[ $key ],
				);
			}
		}

		foreach ( $new_response as $key => $value ) {
			if ( ! isset( $old_response[ $key ] ) ) {
				$differences[ $key ] = array( 'missing_in_old' => $value );
			}
		}

		return $differences;
	}

	/**
	 * Test the User Update endpoint.
	 *
	 * @param int   $user_id User ID to test.
	 * @param array $data    Update data to test.
	 * @return array Test results with success status and message.
	 */
	public function test_update( int $user_id, array $data ): array {
		// Create and send request
		$request = new WP_REST_Request( 'PUT', '/athlete-dashboard/v1/profile/user' );
		$request->set_param( 'user_id', $user_id );

		// Add update data to request
		foreach ( $data as $key => $value ) {
			$request->set_param( $key, $value );
		}

		// Add nonce for authentication
		$nonce = wp_create_nonce( 'wp_rest' );
		$request->add_header( 'X-WP-Nonce', $nonce );

		// Send request
		$response      = rest_do_request( $request );
		$status        = $response->get_status();
		$response_data = $response->get_data();

		// Verify the update was successful
		if ( $status === 200 ) {
			// Verify the changes were applied
			$user                = get_user_by( 'ID', $user_id );
			$verification_result = $this->verify_user_updates( $user, $data );

			return array(
				'success'  => $verification_result['success'],
				'message'  => $verification_result['message'],
				'response' => $response_data,
			);
		}

		return array(
			'success'  => false,
			'message'  => is_array( $response_data ) && isset( $response_data['message'] )
				? $response_data['message']
				: 'Update failed with status ' . $status,
			'response' => $response_data,
		);
	}

	/**
	 * Verify that user updates were applied correctly.
	 *
	 * @param \WP_User $user Updated user object.
	 * @param array    $data Update data that was applied.
	 * @return array Verification result with success status and message.
	 */
	private function verify_user_updates( \WP_User $user, array $data ): array {
		foreach ( $data as $key => $value ) {
			switch ( $key ) {
				case 'email':
					if ( $user->user_email !== $value ) {
						return array(
							'success' => false,
							'message' => "Email was not updated correctly. Expected: $value, Got: {$user->user_email}",
						);
					}
					break;

				case 'meta':
					foreach ( $value as $meta_key => $meta_value ) {
						$actual = get_user_meta( $user->ID, $meta_key, true );
						if ( $actual != $meta_value ) {
							return array(
								'success' => false,
								'message' => "Meta key '$meta_key' was not updated correctly. Expected: " .
											print_r( $meta_value, true ) . ', Got: ' . print_r( $actual, true ),
							);
						}
					}
					break;
			}
		}

		return array(
			'success' => true,
			'message' => 'All updates verified successfully',
		);
	}
}
