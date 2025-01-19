<?php
/**
 * Physical History Endpoint.
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
 * Class Physical_History
 *
 * Handles GET requests for physical measurement history.
 */
class Physical_History extends Base_Endpoint {
	/**
	 * Get the endpoint route.
	 *
	 * @return string
	 */
	public function get_route(): string {
		return '/profile/physical/(?P<user_id>\d+)/history';
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
		$user_id = $request->get_param( 'user_id' );
		return $this->check_resource_owner( $user_id );
	}

	/**
	 * Handle the request.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function handle_request( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		error_log( '=== Physical History Endpoint - Starting Request ===' );
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
		$args    = array(
			'limit'  => $request->get_param( 'limit' ) ?? 10,
			'offset' => $request->get_param( 'offset' ) ?? 0,
		);

		error_log( 'User ID: ' . $user_id );
		error_log( 'Query Args: ' . wp_json_encode( $args ) );

		// Get physical history through service
		$result = $this->service->get_physical_history( $user_id, $args );
		if ( is_wp_error( $result ) ) {
			error_log( 'Service Error: ' . $result->get_error_message() );
			return $this->response_factory->error(
				$result->get_error_message(),
				$result->get_error_data()['status'] ?? 500
			);
		}

		// Format response according to PhysicalHistoryResponse interface
		$response = array(
			'items'  => $result['items'] ?? array(),
			'total'  => $result['total'] ?? 0,
			'limit'  => $args['limit'],
			'offset' => $args['offset'],
		);

		error_log( 'Service Response: ' . wp_json_encode( $response ) );
		error_log( '=== Physical History Endpoint - Request Complete ===' );

		return $this->response_factory->success( $response );
	}

	/**
	 * Get the schema for the endpoint.
	 *
	 * @return array
	 */
	protected function get_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'physical_history',
			'type'       => 'object',
			'properties' => array(
				'user_id' => array(
					'description' => __( 'The user ID to retrieve history for.', 'athlete-dashboard' ),
					'type'        => 'integer',
					'required'    => true,
				),
				'limit'   => array(
					'description' => __( 'Number of records to return.', 'athlete-dashboard' ),
					'type'        => 'integer',
					'minimum'     => 1,
					'maximum'     => 100,
					'default'     => 10,
				),
				'offset'  => array(
					'description' => __( 'Number of records to skip.', 'athlete-dashboard' ),
					'type'        => 'integer',
					'minimum'     => 0,
					'default'     => 0,
				),
				'items'   => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'     => array(
								'type' => 'integer',
							),
							'date'   => array(
								'type'   => 'string',
								'format' => 'date-time',
							),
							'height' => array(
								'type' => 'number',
							),
							'weight' => array(
								'type' => 'number',
							),
							'chest' => array(
								'type' => 'number',
							),
							'waist' => array(
								'type' => 'number',
							),
							'hips' => array(
								'type' => 'number',
							),
							'units'  => array(
								'type'       => 'object',
								'properties' => array(
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
						),
					),
				),
				'total'   => array(
					'type' => 'integer',
				),
			),
		);
	}
}
