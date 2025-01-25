<?php
/**
 * Profile Routes class.
 *
 * @package AthleteDashboard\Features\Profile\API
 */

namespace AthleteDashboard\Features\Profile\API;

use AthleteDashboard\Features\Profile\API\Registry\Endpoint_Registry;
use AthleteDashboard\Features\Profile\API\Endpoints\User\User_Get;
use AthleteDashboard\Features\Profile\Services\Profile_Service;
use AthleteDashboard\Features\Profile\API\Response_Factory;
use Exception;

/**
 * Class Profile_Routes
 *
 * Handles registration of profile-related REST API routes.
 */
class Profile_Routes {
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
	 * Endpoint registry instance.
	 *
	 * @var Endpoint_Registry
	 */
	protected Endpoint_Registry $registry;

	/**
	 * Constructor.
	 *
	 * @param Profile_Service   $service          Profile service instance.
	 * @param Response_Factory  $response_factory Response factory instance.
	 * @param Endpoint_Registry $registry         Endpoint registry instance.
	 */
	public function __construct(
		Profile_Service $service,
		Response_Factory $response_factory,
		Endpoint_Registry $registry
	) {
		$this->service          = $service;
		$this->response_factory = $response_factory;
		$this->registry         = $registry;
	}

	/**
	 * Initialize the routes.
	 *
	 * @return void
	 */
	public function init(): void {
		// Register routes at a higher priority to ensure they run after cleanup
		add_action(
			'rest_api_init',
			function () {
				error_log( '🚀 Registering profile routes at priority 35 (after cleanup)' );
				$this->register_routes();
			},
			35
		);

		error_log( '✅ Profile_Routes initialization complete' );
	}

	/**
	 * Register the profile routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		error_log( '=== DEBUG: Profile_Routes::register_routes() START ===' );

		try {
			// Create User_Get endpoint
			$user_get = new User_Get( $this->service, $this->response_factory );
			error_log( '✨ Created User_Get endpoint instance' );

			// Log endpoint details
			error_log( '🔍 User_Get route: ' . $user_get->get_route() );

			// Register the endpoint with the registry
			$this->registry->register_endpoint( $user_get );
			error_log( '✅ Registered User_Get endpoint with registry' );

			// Log all registered routes
			$routes = rest_get_server()->get_routes();
			error_log( '📋 All registered routes:' );
			foreach ( $routes as $route => $handlers ) {
				if ( strpos( $route, 'athlete-dashboard' ) !== false ) {
					error_log( "Route: $route" );
					error_log( 'Methods: ' . print_r( array_keys( $handlers[0] ), true ) );
					error_log( 'Callback: ' . ( is_array( $handlers[0]['callback'] ) ? get_class( $handlers[0]['callback'][0] ) : 'Closure' ) );
					error_log( 'Permission: ' . ( isset( $handlers[0]['permission_callback'] ) ? 'Present' : 'Missing' ) );
				}
			}

			// Log specific profile routes
			$this->log_route_registration();

			error_log( '=== DEBUG: Profile_Routes::register_routes() END ===' );
		} catch ( Exception $e ) {
			error_log( '❌ Failed to register profile routes: ' . $e->getMessage() );
			error_log( '📋 Exception trace: ' . $e->getTraceAsString() );
			throw $e;
		}
	}

	/**
	 * Log route registration details for debugging.
	 *
	 * @return void
	 */
	private function log_route_registration(): void {
		error_log( '📋 Checking registered routes in Profile_Routes' );

		$routes         = rest_get_server()->get_routes();
		$profile_routes = array_filter(
			$routes,
			function ( $route ) {
				return strpos( $route, 'athlete-dashboard/v1/profile' ) === 0;
			},
			ARRAY_FILTER_USE_KEY
		);

		if ( empty( $profile_routes ) ) {
			error_log( '⚠️ No profile routes found' );
			return;
		}

		foreach ( $profile_routes as $route => $handlers ) {
			error_log( "🔍 Route: $route" );
			foreach ( $handlers as $index => $handler ) {
				error_log( "  📌 Handler #$index:" );
				error_log( '    Methods: ' . implode( ', ', array_keys( $handler ) ) );
				if ( isset( $handler['callback'] ) ) {
					error_log( '    Callback: ' . ( is_array( $handler['callback'] ) ? get_class( $handler['callback'][0] ) : 'Closure' ) );
				}
				if ( isset( $handler['permission_callback'] ) ) {
					error_log( '    Permission callback: Present' );
				}
			}
		}
	}

	/**
	 * Get the profile service instance.
	 *
	 * @return Profile_Service
	 */
	public function get_service(): Profile_Service {
		return $this->service;
	}
}
