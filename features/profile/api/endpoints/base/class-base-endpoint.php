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
		// Set our properties
		$this->service          = $service;
		$this->response_factory = $response_factory;

		// Set REST API namespace and base
		$this->namespace = 'athlete-dashboard/v1';
		$this->rest_base = 'profile/user';

		error_log( '🔧 DEBUG: Base_Endpoint constructor called' );
		error_log( '🔧 DEBUG: Namespace: ' . $this->namespace );
		error_log( '🔧 DEBUG: Rest base: ' . $this->rest_base );
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
	abstract public function get_schema(): ?array;

	/**
	 * Register the endpoint's routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		error_log( '🚀 DEBUG: Base_Endpoint Route Registration Starting' );
		error_log( '📝 DEBUG: Namespace: ' . $this->namespace );
		error_log( '📝 DEBUG: Rest Base: ' . $this->rest_base );
		error_log( '📝 DEBUG: Route from child: ' . $this->get_route() );

		$route = $this->get_route();
		// Remove leading slash if present to prevent double slashes
		$route = ltrim( $route, '/' );

		error_log( '📝 DEBUG: Final route path: ' . $route );

		// Register the route
		register_rest_route(
			$this->namespace,
			$route,
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

		// Verify the route was registered
		$routes         = rest_get_server()->get_routes();
		$complete_route = '/' . $this->namespace . '/' . $route;
		if ( isset( $routes[ $complete_route ] ) ) {
			error_log( '✅ DEBUG: Route successfully registered: ' . $complete_route );
			error_log( '📋 DEBUG: Route handlers: ' . print_r( $routes[ $complete_route ], true ) );
		} else {
			error_log( '❌ DEBUG: Failed to register route: ' . $complete_route );
			error_log( '📋 DEBUG: Available routes: ' . print_r( array_keys( $routes ), true ) );
		}

		error_log( '🏁 DEBUG: Route Registration Complete' );
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
	 * Check if the current user has permission to access this endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if the request has access, WP_Error object otherwise.
	 */
	abstract public function check_permission( WP_REST_Request $request ): bool|WP_Error;

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

	/**
	 * Check the nonce for the request.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool|WP_Error True if nonce is valid, WP_Error otherwise.
	 */
	protected function check_nonce( WP_REST_Request $request ): bool|WP_Error {
		// Skip nonce check in test environment
		if ( defined( 'WP_TESTS_DOMAIN' ) ) {
			return true;
		}

		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Missing nonce.', 'athlete-dashboard' ),
				array( 'status' => 401 )
			);
		}

		$result = wp_verify_nonce( $nonce, 'wp_rest' );
		if ( ! $result ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Invalid nonce.', 'athlete-dashboard' ),
				array( 'status' => 401 )
			);
		}

		return true;
	}
}
