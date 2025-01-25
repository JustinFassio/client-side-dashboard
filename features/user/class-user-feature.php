<?php
/**
 * User Feature.
 *
 * @package AthleteDashboard\Features\User
 */

namespace AthleteDashboard\Features\User;

use AthleteDashboard\Core\Container;
use AthleteDashboard\Core\Feature;

/**
 * Main class for the User feature.
 */
class User_Feature implements Feature {
	/**
	 * Bootstrap instance.
	 *
	 * @var User_Bootstrap
	 */
	private User_Bootstrap $bootstrap;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->bootstrap = new User_Bootstrap();
	}

	/**
	 * Bootstrap the feature.
	 *
	 * @param Container $container Service container instance.
	 */
	public function bootstrap( Container $container ): void {
		// Register services
		$this->bootstrap->register_services( $container );

		// Register event listeners
		$this->bootstrap->register_events( $container );

		// Register REST API endpoints
		add_action(
			'rest_api_init',
			function () use ( $container ) {
				$this->register_rest_routes( $container );
			}
		);
	}

	/**
	 * Register REST API routes.
	 *
	 * @param Container $container Service container instance.
	 */
	private function register_rest_routes( Container $container ): void {
		$endpoints = new Api\User_Endpoints(
			$container->get( Services\User_Service::class )
		);
		$endpoints->register_routes();
	}
}
