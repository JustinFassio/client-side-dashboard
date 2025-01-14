<?php
/**
 * Profile Feature Bootstrap.
 *
 * @package AthleteDashboard\Features\Profile
 */

namespace AthleteDashboard\Features\Profile;

use AthleteDashboard\Core\Events;
use AthleteDashboard\Core\Container;
use AthleteDashboard\Features\Profile\Events\Listeners\User_Updated_Listener;
use AthleteDashboard\Features\Profile\Services\Profile_Service;
use AthleteDashboard\Features\User\Events\User_Updated;

/**
 * Bootstrap class for the Profile feature.
 */
class Profile_Bootstrap {
	/**
	 * Register event listeners for the Profile feature.
	 *
	 * @param Container $container Service container instance.
	 */
	public function register_events( Container $container ): void {
		// Register the User Updated listener
		Events::listen(
			User_Updated::class,
			function ( User_Updated $event ) use ( $container ): void {
				$listener = new User_Updated_Listener(
					$container->get( Profile_Service::class )
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
		// Bind the Profile Repository
		$container->singleton(
			Repository\Profile_Repository::class,
			fn() => new Repository\Profile_Repository()
		);

		// Bind the Profile Validator
		$container->singleton(
			Validation\Profile_Validator::class,
			fn() => new Validation\Profile_Validator()
		);

		// Bind the Profile Service
		$container->singleton(
			Services\Profile_Service::class,
			fn( Container $container ) => new Services\Profile_Service(
				$container->get( Repository\Profile_Repository::class ),
				$container->get( Validation\Profile_Validator::class )
			)
		);
	}
}
