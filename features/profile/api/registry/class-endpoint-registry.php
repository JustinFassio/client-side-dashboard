<?php
/**
 * Endpoint Registry.
 *
 * @package AthleteDashboard\Features\Profile\API\Registry
 */

namespace AthleteDashboard\Features\Profile\API\Registry;

use AthleteDashboard\Features\Profile\API\Endpoints\Base\Base_Endpoint;

/**
 * Class Endpoint_Registry
 *
 * Manages registration of REST API endpoints.
 */
class Endpoint_Registry {
	/**
	 * Registered endpoints.
	 *
	 * @var Base_Endpoint[]
	 */
	private array $endpoints = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_all_endpoints' ), 40 );
	}

	/**
	 * Register an endpoint.
	 *
	 * @param Base_Endpoint $endpoint Endpoint instance.
	 * @return void
	 */
	public function register_endpoint( Base_Endpoint $endpoint ): void {
		error_log( '🔧 DEBUG: Endpoint_Registry storing endpoint: ' . get_class( $endpoint ) );
		error_log( '🔧 DEBUG: Endpoint route: ' . $endpoint->get_route() );

		$this->endpoints[] = $endpoint;
	}

	/**
	 * Register all stored endpoints during rest_api_init.
	 *
	 * @return void
	 */
	public function register_all_endpoints(): void {
		error_log( '🚀 DEBUG: Endpoint_Registry registering all endpoints' );

		foreach ( $this->endpoints as $endpoint ) {
			try {
				error_log( '🔧 DEBUG: Registering routes for endpoint: ' . get_class( $endpoint ) );
				$endpoint->register_routes();
				error_log( '✅ DEBUG: Successfully registered routes for endpoint: ' . get_class( $endpoint ) );
			} catch ( \Exception $e ) {
				error_log( '❌ DEBUG: Failed to register routes for endpoint: ' . get_class( $endpoint ) );
				error_log( '❌ DEBUG: Error: ' . $e->getMessage() );
				throw $e;
			}
		}

		error_log( '✨ DEBUG: Endpoint_Registry finished registering all endpoints' );
	}

	/**
	 * Get all registered endpoints.
	 *
	 * @return Base_Endpoint[]
	 */
	public function get_endpoints(): array {
		return $this->endpoints;
	}
}
