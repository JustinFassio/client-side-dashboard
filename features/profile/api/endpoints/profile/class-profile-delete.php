<?php
/**
 * Profile Delete Endpoint.
 *
 * @package AthleteDashboard\Features\Profile\API\Endpoints\Profile
 */

namespace AthleteDashboard\Features\Profile\API\Endpoints\Profile;

use AthleteDashboard\Features\Profile\API\Endpoints\Base\Base_Endpoint;
use AthleteDashboard\Features\Profile\API\Endpoints\Base\Auth_Checks;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Class Profile_Delete
 *
 * Handles DELETE requests for profile data.
 */
class Profile_Delete extends Base_Endpoint {
	use Auth_Checks;

	/**
	 * Get the endpoint's REST route.
	 *
	 * @return string Route path relative to the base.
	 */
	public function get_route(): string {
		return '/' . $this->rest_base;
	}

	/**
	 * Get the endpoint's HTTP method.
	 *
	 * @return string HTTP method.
	 */
	public function get_method(): string {
		return WP_REST_Server::DELETABLE;
	}

	/**
	 * Get endpoint arguments for request validation.
	 *
	 * @return array Endpoint arguments.
	 */
	protected function get_endpoint_args(): array {
		return array(
			'force' => array(
				'type'        => 'boolean',
				'description' => __( 'Whether to force hard deletion instead of soft deletion.', 'athlete-dashboard' ),
				'required'    => false,
				'default'     => false,
			),
		);
	}

	/**
	 * Handle the DELETE request for profile data.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function handle_request( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		try {
			$user_id = $this->get_current_user_id();

			// Run auth checks with additional capability check for deletion
			$auth_result = $this->run_auth_checks(
				array(
					array( 'method' => 'check_logged_in' ),
					array(
						'method' => 'check_resource_owner',
						'args'   => array( $user_id ),
					),
					array(
						'method' => 'check_capability',
						'args'   => array( 'delete_user' ),
					),
					array(
						'method' => 'verify_nonce',
						'args'   => array( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' ),
					),
				)
			);

			if ( is_wp_error( $auth_result ) ) {
				return $this->error(
					$auth_result->get_error_message(),
					$auth_result->get_error_data()['status']
				);
			}

			// Check if profile exists
			if ( ! $this->service->profile_exists( $user_id ) ) {
				return $this->error(
					__( 'Profile not found.', 'athlete-dashboard' ),
					404
				);
			}

			// Get force delete parameter
			$force = $request->get_param( 'force' );

			// Delete profile using service
			$delete_result = $this->service->delete_profile( $user_id, $force );
			if ( is_wp_error( $delete_result ) ) {
				return $this->error(
					$delete_result->get_error_message(),
					$delete_result->get_error_data()['status'] ?? 500
				);
			}

			// Return 204 No Content for successful deletion
			return $this->success( array(), 204 );
		} catch ( \Exception $e ) {
			return $this->error(
				__( 'Failed to delete profile.', 'athlete-dashboard' ),
				500,
				array( 'error' => $e->getMessage() )
			);
		}
	}

	/**
	 * Get the schema for profile deletion.
	 *
	 * @return array Schema data.
	 */
	protected function get_schema(): array {
		if ( $this->schema ) {
			return $this->schema;
		}

		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'profile-delete',
			'type'       => 'object',
			'properties' => array(
				'force' => array(
					'type'        => 'boolean',
					'description' => __( 'Whether to force hard deletion instead of soft deletion.', 'athlete-dashboard' ),
					'default'     => false,
				),
			),
		);

		return $this->schema;
	}
}
