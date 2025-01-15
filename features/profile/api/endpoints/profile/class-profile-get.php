<?php
/**
 * Profile Get Endpoint.
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
 * Class Profile_Get
 *
 * Handles GET requests for profile data.
 */
class Profile_Get extends Base_Endpoint {
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
		return WP_REST_Server::READABLE;
	}

	/**
	 * Handle the GET request for profile data.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function handle_request( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		try {
			$user_id = $this->get_current_user_id();

			// Run auth checks
			$auth_result = $this->run_auth_checks(
				array(
					array( 'method' => 'check_logged_in' ),
					array(
						'method' => 'check_resource_owner',
						'args'   => array( $user_id ),
					),
				)
			);

			if ( is_wp_error( $auth_result ) ) {
				return $this->error(
					$auth_result->get_error_message(),
					$auth_result->get_error_data()['status']
				);
			}

			// Get profile data from service
			$profile = $this->service->get_profile( $user_id );
			if ( is_wp_error( $profile ) ) {
				return $this->error(
					$profile->get_error_message(),
					$profile->get_error_data()['status'] ?? 500
				);
			}

			return $this->success( array( 'profile' => $profile ) );
		} catch ( \Exception $e ) {
			return $this->error(
				__( 'Failed to retrieve profile data.', 'athlete-dashboard' ),
				500,
				array( 'error' => $e->getMessage() )
			);
		}
	}

	/**
	 * Get the schema for profile data.
	 *
	 * @return array Schema data.
	 */
	protected function get_schema(): array {
		if ( $this->schema ) {
			return $this->schema;
		}

		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'profile',
			'type'       => 'object',
			'properties' => array(
				'id'          => array(
					'type'        => 'integer',
					'description' => __( 'User ID.', 'athlete-dashboard' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'email'       => array(
					'type'        => 'string',
					'format'      => 'email',
					'description' => __( 'User email address.', 'athlete-dashboard' ),
					'context'     => array( 'view', 'edit' ),
				),
				'firstName'   => array(
					'type'        => 'string',
					'description' => __( 'User first name.', 'athlete-dashboard' ),
					'context'     => array( 'view', 'edit' ),
				),
				'lastName'    => array(
					'type'        => 'string',
					'description' => __( 'User last name.', 'athlete-dashboard' ),
					'context'     => array( 'view', 'edit' ),
				),
				'displayName' => array(
					'type'        => 'string',
					'description' => __( 'User display name.', 'athlete-dashboard' ),
					'context'     => array( 'view', 'edit' ),
				),
				'roles'       => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => __( 'User roles.', 'athlete-dashboard' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->schema;
	}
}
