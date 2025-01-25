<?php
/**
 * Response Factory class.
 *
 * @package AthleteDashboard\Features\Profile\API
 */

namespace AthleteDashboard\Features\Profile\API;

use WP_REST_Response;
use WP_Error;

/**
 * Class Response_Factory
 *
 * Handles creation of standardized REST API responses.
 */
class Response_Factory {
	/**
	 * Create a success response.
	 *
	 * @param array $data The response data.
	 * @param int   $status Optional HTTP status code. Defaults to 200.
	 * @return WP_REST_Response
	 */
	public function success( array $data, int $status = 200 ): WP_REST_Response {
		$response = rest_ensure_response(
			array(
				'success' => true,
				'data'    => $data,
			)
		);
		$response->set_status( $status );
		return $response;
	}

	/**
	 * Create an error response.
	 *
	 * @param string $message Error message.
	 * @param int    $code HTTP status code.
	 * @param array  $additional_data Optional additional error data.
	 * @return WP_REST_Response
	 */
	public function error( string $message, int $code = 500, array $additional_data = array() ): WP_REST_Response {
		$error_data = array(
			'message' => $message,
			'code'    => $code,
		);

		if ( ! empty( $additional_data ) ) {
			$error_data['data'] = $additional_data;
		}

		$response = rest_ensure_response(
			array(
				'success' => false,
				'error'   => $error_data,
			)
		);
		$response->set_status( $code );
		return $response;
	}

	/**
	 * Create a validation error response.
	 *
	 * @param WP_Error $error WordPress error object.
	 * @return WP_REST_Response
	 */
	public function validation_error( WP_Error $error ): WP_REST_Response {
		$response = rest_ensure_response(
			array(
				'success' => false,
				'error'   => array(
					'message' => $error->get_error_message(),
					'code'    => 400,
					'data'    => $error->get_error_data(),
				),
			)
		);
		$response->set_status( 400 );
		return $response;
	}

	/**
	 * Create a not found error response.
	 *
	 * @param string $message Error message.
	 * @return WP_REST_Response
	 */
	public function not_found( string $message ): WP_REST_Response {
		return $this->error( $message, 404 );
	}

	/**
	 * Create an unauthorized error response.
	 *
	 * @param string $message Error message.
	 * @return WP_REST_Response
	 */
	public function unauthorized( string $message ): WP_REST_Response {
		return $this->error( $message, 401 );
	}

	/**
	 * Create a forbidden error response.
	 *
	 * @param string $message Error message.
	 * @return WP_REST_Response
	 */
	public function forbidden( string $message ): WP_REST_Response {
		return $this->error( $message, 403 );
	}
}
