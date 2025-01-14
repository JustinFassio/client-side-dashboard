<?php
/**
 * User Feature Bootstrap.
 *
 * @package AthleteDashboard\Features\User
 */

namespace AthleteDashboard\Features\User;

use AthleteDashboard\Core\Events;
use AthleteDashboard\Core\Container;
use AthleteDashboard\Features\User\Events\Listeners\Profile_Updated_Listener;
use AthleteDashboard\Features\User\Services\User_Service;
use AthleteDashboard\Features\Profile\Events\Profile_Updated;

/**
 * Bootstrap class for the User feature.
 */
class User_Bootstrap {
	/**
	 * Register event listeners for the User feature.
	 *
	 * @param Container $container Service container instance.
	 */
	public function register_events( Container $container ): void {
		// Register the Profile Updated listener
		Events::listen(
			Profile_Updated::class,
			function ( Profile_Updated $event ) use ( $container ): void {
				$listener = new Profile_Updated_Listener(
					$container->get( User_Service::class )
				);
				$listener->handle( $event );
			}
		);
	}

	/**
	 * Register service bindings.
	 *
	 * @param Container $container Service container instance.
	 */
	public function register_services( Container $container ): void {
		// Bind the User Repository
		$container->singleton(
			Repository\User_Repository::class,
			fn() => new Repository\User_Repository()
		);

		// Bind the User Validator
		$container->singleton(
			Validation\User_Validator::class,
			fn() => new Validation\User_Validator()
		);

		// Bind the User Service
		$container->singleton(
			Services\User_Service::class,
			fn( Container $container ) => new Services\User_Service(
				$container->get( Repository\User_Repository::class ),
				$container->get( Validation\User_Validator::class )
			)
		);
	}
}
