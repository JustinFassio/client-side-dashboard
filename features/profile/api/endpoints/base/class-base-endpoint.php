<?php
/**
 * Base Endpoint class.
 *
 * @package AthleteDashboard\Features\Profile\API\Endpoints\Base
 */

namespace AthleteDashboard\Features\Profile\API\Endpoints\Base;

use AthleteDashboard\Features\Profile\API\Response_Factory;
use AthleteDashboard\Features\Profile\Services\Profile_Service;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Abstract base class for profile endpoints.
 */
abstract class Base_Endpoint extends WP_REST_Controller {
	/**
	 * Profile service instance.
	 *
	 * @var Profile_Service
	 */
	protected Profile_Service $service;

	/**
	 * Response factory instance.
	 *
	 * @var Response_Factory
	 */
	protected Response_Factory $response_factory;

	/**
	 * Constructor.
	 *
	 * @param Profile_Service  $service         Profile service instance.
	 * @param Response_Factory $response_factory Response factory instance.
	 */
	public function __construct( Profile_Service $service, Response_Factory $response_factory ) {
		$this->namespace        = 'athlete-dashboard/v1';
		$this->rest_base        = 'profile';
		$this->service          = $service;
		$this->response_factory = $response_factory;
	}

	/**
	 * Get the endpoint's REST route.
	 *
	 * @return string Route path relative to the base.
	 */
	abstract public function get_route(): string;

	/**
	 * Get the endpoint's HTTP method.
	 *
	 * @return string HTTP method (GET, POST, etc.).
	 */
	abstract public function get_method(): string;

	/**
	 * Handle the request.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	abstract public function handle_request( WP_REST_Request $request ): WP_REST_Response|WP_Error;

	/**
	 * Get endpoint schema.
	 *
	 * @return array|null Schema array or null if none.
	 */
	abstract protected function get_schema(): ?array;

	/**
	 * Register the endpoint's routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			$this->get_route(),
			array(
				array(
					'methods'             => $this->get_method(),
					'callback'            => array( $this, 'handle_request' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => $this->get_endpoint_args(),
					'schema'              => array( $this, 'get_schema' ),
				),
			)
		);
	}

	/**
	 * Get endpoint arguments.
	 *
	 * @return array Endpoint arguments.
	 */
	protected function get_endpoint_args(): array {
		return array();
	}

	/**
	 * Check if the request has permission to proceed.
	 *
	 * @return bool|WP_Error True if has permission, WP_Error if not.
	 */
	protected function check_permission(): bool|WP_Error {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in to access this endpoint.', 'athlete-dashboard' ),
				array( 'status' => 401 )
			);
		}
		return true;
	}

	/**
	 * Get the current user ID.
	 *
	 * @return int Current user ID.
	 */
	protected function get_current_user_id(): int {
		return get_current_user_id();
	}

	/**
	 * Create a success response.
	 *
	 * @param array $data Response data.
	 * @param int   $status HTTP status code.
	 * @return WP_REST_Response
	 */
	protected function success( array $data, int $status = 200 ): WP_REST_Response {
		return $this->response_factory->success( $data, $status );
	}

	/**
	 * Create an error response.
	 *
	 * @param string $message Error message.
	 * @param int    $code    HTTP status code.
	 * @param array  $data    Additional error data.
	 * @return WP_REST_Response
	 */
	protected function error( string $message, int $code = 500, array $data = array() ): WP_REST_Response {
		return $this->response_factory->error( $message, $code, $data );
	}

	/**
	 * Create a validation error response.
	 *
	 * @param WP_Error $error WordPress error object.
	 * @return WP_REST_Response
	 */
	protected function validation_error( WP_Error $error ): WP_REST_Response {
		return $this->response_factory->validation_error( $error );
	}
}
