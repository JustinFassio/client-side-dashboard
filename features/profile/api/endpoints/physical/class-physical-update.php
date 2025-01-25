<?php
/**
 * Physical Update Endpoint.
 *
 * @package AthleteDashboard\Features\Profile\API\Endpoints\Physical
 */

namespace AthleteDashboard\Features\Profile\API\Endpoints\Physical;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use AthleteDashboard\Features\Profile\API\Endpoints\Physical\Base_Endpoint;

/**
 * Class Physical_Update
 *
 * Handles POST/PUT requests for physical measurement data.
 */
class Physical_Update extends Base_Endpoint {
	/**
	 * Get the endpoint route.
	 *
	 * @return string
	 */
	public function get_route(): string {
		return '/profile/physical/(?P<user_id>\d+)';
	}

	/**
	 * Get the endpoint's HTTP method.
	 *
	 * @return string
	 */
	public function get_method(): string {
		return WP_REST_Server::CREATABLE;
	}

	/**
	 * Check if the request has permission to access this endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if has permission, WP_Error if not.
	 */
	public function check_permission( WP_REST_Request $request ): bool|WP_Error {
		error_log( 'Physical_Update: Starting permission check' );
		error_log( 'Physical_Update: Current user ID: ' . get_current_user_id() );
		error_log( 'Physical_Update: Is user logged in: ' . ( is_user_logged_in() ? 'Yes' : 'No' ) );
		error_log( 'Physical_Update: Request nonce: ' . $request->get_header( 'X-WP-Nonce' ) );

		$user_id = $request->get_param( 'user_id' );
		error_log( 'Physical_Update: Requested user ID: ' . $user_id );

		$result = $this->check_resource_owner( $user_id );
		error_log( 'Physical_Update: Resource owner check result: ' . ( is_wp_error( $result ) ? $result->get_error_message() : 'Success' ) );

		return $result;
	}

	/**
	 * Handle the request.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function handle_request( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		error_log( '=== Physical Update Endpoint - Starting Request ===' );
		error_log(
			'Request Details: ' . wp_json_encode(
				array(
					'params'  => $request->get_params(),
					'headers' => $request->get_headers(),
					'method'  => $request->get_method(),
				)
			)
		);

		$user_id = $request->get_param( 'user_id' );
		$data    = $request->get_json_params();

		error_log( 'User ID: ' . $user_id );
		error_log( 'Update Data: ' . wp_json_encode( $data ) );

		// Update physical data through service
		$result = $this->service->update_physical_data( $user_id, $data );
		if ( is_wp_error( $result ) ) {
			error_log( 'Service Error: ' . $result->get_error_message() );
			return $this->response_factory->error(
				$result->get_error_message(),
				$result->get_error_data()['status'] ?? 500
			);
		}

		error_log( 'Service Response: ' . wp_json_encode( $result ) );
		error_log( '=== Physical Update Endpoint - Request Complete ===' );

		return $this->response_factory->success( $result );
	}

	/**
	 * Get the schema for the endpoint.
	 *
	 * @return array
	 */
	public function get_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'physical',
			'type'       => 'object',
			'required'   => array( 'height', 'weight', 'units' ),
			'properties' => array(
				'user_id'     => array(
					'description' => __( 'The user ID to update physical data for.', 'athlete-dashboard' ),
					'type'        => 'integer',
					'required'    => true,
				),
				'height'      => array(
					'description' => __( 'The user\'s height.', 'athlete-dashboard' ),
					'type'        => 'number',
					'required'    => true,
				),
				'weight'      => array(
					'description' => __( 'The user\'s weight.', 'athlete-dashboard' ),
					'type'        => 'number',
					'required'    => true,
				),
				'units'       => array(
					'description' => __( 'The measurement units.', 'athlete-dashboard' ),
					'type'        => 'object',
					'required'    => true,
					'properties'  => array(
						'height' => array(
							'type' => 'string',
							'enum' => array( 'cm', 'ft' ),
						),
						'weight' => array(
							'type' => 'string',
							'enum' => array( 'kg', 'lbs' ),
						),
					),
				),
				'preferences' => array(
					'description' => __( 'Physical data preferences.', 'athlete-dashboard' ),
					'type'        => 'object',
					'required'    => false,
					'properties'  => array(
						'showMetric' => array(
							'type' => 'boolean',
						),
					),
				),
			),
		);
	}

	/**
	 * Get the schema for the preferences object.
	 *
	 * @return array Schema array.
	 */
	private function get_preferences_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'showMetric' => array(
					'type'        => 'boolean',
					'description' => __( 'Whether to show metric units', 'athlete-dashboard' ),
					'default'     => true,
				),
			),
		);
	}
}
