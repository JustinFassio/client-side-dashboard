<?php
/**
 * Profile Feature.
 *
 * @package AthleteDashboard\Features\Profile
 */

namespace AthleteDashboard\Features\Profile;

use AthleteDashboard\Core\Container;
use AthleteDashboard\Core\Feature;

/**
 * Main class for the Profile feature.
 */
class Profile_Feature implements Feature {
	/**
	 * Bootstrap instance.
	 *
	 * @var Profile_Bootstrap
	 */
	private Profile_Bootstrap $bootstrap;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->bootstrap = new Profile_Bootstrap();
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
		$endpoints = new Api\Profile_Endpoints(
			$container->get( Services\Profile_Service::class )
		);
		$endpoints->register_routes();
	}
}
