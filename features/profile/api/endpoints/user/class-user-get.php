<?php
/**
 * User Get Endpoint.
 *
 * @package AthleteDashboard\Features\Profile\API\Endpoints\User
 */

namespace AthleteDashboard\Features\Profile\API\Endpoints\User;

use AthleteDashboard\Features\Profile\API\Endpoints\Base\Base_Endpoint;
use AthleteDashboard\Features\Profile\API\Endpoints\Base\Auth_Checks;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Class User_Get
 *
 * Handles GET requests for user data.
 */
class User_Get extends Base_Endpoint {
	use Auth_Checks;

	/**
	 * Get the endpoint route.
	 *
	 * @return string
	 */
	public function get_route(): string {
		return '/profile/user';
	}

	/**
	 * Get the endpoint's HTTP method.
	 *
	 * @return string
	 */
	public function get_method(): string {
		return WP_REST_Server::READABLE;
	}

	/**
	 * Check if the request has permission to access this endpoint.
	 *
	 * @return bool|WP_Error True if has permission, WP_Error if not.
	 */
	public function check_permission(): bool|WP_Error {
		return $this->check_logged_in();
	}

	/**
	 * Handle the request.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function handle_request( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		error_log(
			'New User Get Endpoint - Request: ' . wp_json_encode(
				array(
					'params'  => $request->get_params(),
					'headers' => $request->get_headers(),
					'method'  => $request->get_method(),
				)
			)
		);

		// Get user ID from request or use current user
		$user_id = $request->get_param( 'user_id' ) ?? get_current_user_id();
		error_log( 'New User Get Endpoint - User ID: ' . $user_id );

		// Get user data
		$result = $this->service->get_user_data( $user_id );
		if ( is_wp_error( $result ) ) {
			error_log( 'New User Get Endpoint - Service Error: ' . $result->get_error_message() );
			return $this->response_factory->error(
				$result->get_error_message(),
				$result->get_error_data()['status'] ?? 500
			);
		}

		error_log( 'New User Get Endpoint - Success Response: ' . wp_json_encode( $result ) );
		return $this->response_factory->success( $result );
	}

	/**
	 * Get the schema for the endpoint.
	 *
	 * @return array
	 */
	protected function get_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'user',
			'type'       => 'object',
			'properties' => array(
				'user_id' => array(
					'description' => __( 'The user ID to retrieve data for.', 'athlete-dashboard' ),
					'type'        => 'integer',
					'required'    => false,
				),
			),
		);
	}
}
