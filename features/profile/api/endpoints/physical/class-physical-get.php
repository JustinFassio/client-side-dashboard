<?php
/**
 * Physical Get Endpoint.
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
 * Class Physical_Get
 *
 * Handles GET requests for physical measurement data.
 */
class Physical_Get extends Base_Endpoint {
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
		return WP_REST_Server::READABLE;
	}

	/**
	 * Check if the request has permission to access this endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if has permission, WP_Error if not.
	 */
	public function check_permission( WP_REST_Request $request ): bool|WP_Error {
		error_log( '=== Physical Get Endpoint - Permission Check ===' );
		error_log( 'Current User ID: ' . get_current_user_id() );

		$user_id = $request->get_param( 'user_id' );
		error_log( 'Requested User ID: ' . $user_id );

		$logged_in = is_user_logged_in();
		error_log( 'Is User Logged In: ' . ( $logged_in ? 'Yes' : 'No' ) );

		if ( ! $logged_in ) {
			error_log( 'Permission denied: User not logged in' );
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in to access this endpoint.', 'athlete-dashboard' ),
				array( 'status' => 401 )
			);
		}

		$can_access = $this->check_resource_owner( $user_id );
		error_log( 'Resource Owner Check Result: ' . ( is_wp_error( $can_access ) ? 'Error: ' . $can_access->get_error_message() : 'Success' ) );

		return $can_access;
	}

	/**
	 * Handle the request.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function handle_request( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		error_log( '=== Physical Get Endpoint - Starting Request ===' );
		error_log( 'Request URI: ' . $_SERVER['REQUEST_URI'] );
		error_log( 'Request Method: ' . $_SERVER['REQUEST_METHOD'] );
		error_log( 'Auth Header: ' . $_SERVER['HTTP_AUTHORIZATION'] ?? 'none' );
		error_log( 'Cookie: ' . $_SERVER['HTTP_COOKIE'] ?? 'none' );
		error_log( 'X-WP-Nonce: ' . $request->get_header( 'X-WP-Nonce' ) );
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
		error_log( 'User ID: ' . $user_id );

		try {
			$data = $this->service->get_physical_data( $user_id );
			error_log( 'Physical_Get: final response: ' . wp_json_encode( $data ) );
			return $this->response_factory->success( $data );
		} catch ( \Exception $e ) {
			error_log( 'Physical_Get: error: ' . $e->getMessage() );
			if ( $e->getCode() === 404 ) {
				return $this->response_factory->error(
					'no_physical_data',
					'No physical data found for user',
					404
				);
			}
			return $this->response_factory->error(
				'physical_data_error',
				$e->getMessage()
			);
		}
	}

	/**
	 * Get the schema for the endpoint.
	 *
	 * @return array
	 */
	protected function get_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'physical',
			'type'       => 'object',
			'properties' => array(
				'user_id'     => array(
					'description' => __( 'The user ID to retrieve physical data for.', 'athlete-dashboard' ),
					'type'        => 'integer',
					'required'    => true,
				),
				'height'      => array(
					'description' => __( 'The user\'s height.', 'athlete-dashboard' ),
					'type'        => 'number',
					'required'    => false,
				),
				'weight'      => array(
					'description' => __( 'The user\'s weight.', 'athlete-dashboard' ),
					'type'        => 'number',
					'required'    => false,
				),
				'chest'       => array(
					'description' => __( 'The user\'s chest measurement.', 'athlete-dashboard' ),
					'type'        => 'number',
					'required'    => false,
				),
				'waist'       => array(
					'description' => __( 'The user\'s waist measurement.', 'athlete-dashboard' ),
					'type'        => 'number',
					'required'    => false,
				),
				'hips'        => array(
					'description' => __( 'The user\'s hips measurement.', 'athlete-dashboard' ),
					'type'        => 'number',
					'required'    => false,
				),
				'units'       => array(
					'description' => __( 'The measurement units.', 'athlete-dashboard' ),
					'type'        => 'object',
					'required'    => false,
					'properties'  => array(
						'height' => array(
							'type' => 'string',
							'enum' => array( 'cm', 'ft' ),
						),
						'weight' => array(
							'type' => 'string',
							'enum' => array( 'kg', 'lbs' ),
						),
						'measurements' => array(
							'type' => 'string',
							'enum' => array( 'cm', 'in' ),
						),
					),
				),
				'preferences' => array(
					'description' => __( 'Physical data preferences.', 'athlete-dashboard' ),
					'type'        => 'object',
					'required'    => false,
					'properties'  => array(
						'showMetric'   => array(
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
