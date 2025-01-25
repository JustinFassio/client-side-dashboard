<?php
/**
 * Profile Feature Bootstrap.
 *
 * @package AthleteDashboard\Features\Profile
 */

namespace AthleteDashboard\Features\Profile;

use AthleteDashboard\Core\Events;
use AthleteDashboard\Core\Container;
use AthleteDashboard\Features\Profile\API\Response_Factory;
use AthleteDashboard\Features\Profile\API\Registry\Endpoint_Registry;
use AthleteDashboard\Features\Profile\API\Profile_Routes;
use AthleteDashboard\Features\Profile\Services;
use AthleteDashboard\Features\Profile\Repository;
use AthleteDashboard\Features\Profile\Validation;
use AthleteDashboard\Features\Profile\Database\Migrations\Physical_Measurements_Table;
use AthleteDashboard\Features\Profile\Events\Listeners\User_Updated_Listener;
use AthleteDashboard\Features\Profile\Services\Profile_Service;
use AthleteDashboard\Features\User\Events\User_Updated;
use AthleteDashboard\Features\Profile\Admin\Profile_Admin;

/**
 * Bootstrap class for the Profile feature.
 */
class Profile_Bootstrap {
	/**
	 * Whether the bootstrap has been initialized.
	 *
	 * @var bool
	 */
	private static $initialized = false;

	/**
	 * Container instance.
	 *
	 * @var Container
	 */
	private $container;

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

		// Bind the Response Factory
		$container->singleton(
			Response_Factory::class,
			fn() => new Response_Factory()
		);

		// Bind the Profile Service
		$container->singleton(
			Services\Profile_Service::class,
			fn( Container $container ) => new Services\Profile_Service(
				$container->get( Repository\Profile_Repository::class ),
				$container->get( Validation\Profile_Validator::class )
			)
		);

		// Bind the Endpoint Registry
		$container->singleton(
			Endpoint_Registry::class,
			fn() => new Endpoint_Registry()
		);

		// Bind the Profile Routes
		$container->singleton(
			Profile_Routes::class,
			fn( Container $container ) => new Profile_Routes(
				$container->get( Services\Profile_Service::class ),
				$container->get( Response_Factory::class ),
				$container->get( Endpoint_Registry::class )
			)
		);
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes(): void {
		if ( ! $this->container ) {
			error_log( '🚫 Profile_Bootstrap: Container not initialized when registering routes' );
			return;
		}

		try {
			error_log( '🚀 Profile_Bootstrap: Starting route registration' );

			// Get routes instance from container
			$routes = $this->container->get( Profile_Routes::class );

			// Initialize routes - this sets up the rest_api_init hook
			$routes->init();

			error_log( '✨ Profile_Bootstrap: Route registration complete' );
		} catch ( \Exception $e ) {
			error_log( '❌ Profile_Bootstrap: Error registering routes: ' . $e->getMessage() );
			error_log( 'Stack trace: ' . $e->getTraceAsString() );
		}
	}

	/**
	 * Run migrations for database setup.
	 */
	public function run_migrations(): void {
		error_log( 'Profile_Bootstrap: Running migrations' );

		$physical_migration = new Physical_Measurements_Table();
		$result             = $physical_migration->up();

		if ( is_wp_error( $result ) ) {
			error_log( 'Profile_Bootstrap: Migration failed - ' . $result->get_error_message() );
		} else {
			error_log( 'Profile_Bootstrap: Migration completed successfully' );
		}
	}

	/**
	 * Initialize the feature.
	 */
	public function init(): void {
		error_log( '🚀 Profile_Bootstrap: Initializing' );

		try {
			// Run migrations on after_switch_theme hook
			add_action( 'after_switch_theme', array( $this, 'run_migrations' ) );

			// Register routes on rest_api_init hook with high priority (after cleanup)
			add_action(
				'rest_api_init',
				function () {
					if ( WP_DEBUG ) {
						error_log( '📝 Profile_Bootstrap: Registering routes at priority 30 (after cleanup)' );
					}
					$this->register_routes();
				},
				30
			);

			error_log( '✅ Profile_Bootstrap: Initialization complete' );
		} catch ( \Exception $e ) {
			error_log( '❌ Profile_Bootstrap: Error during initialization: ' . $e->getMessage() );
			error_log( 'Stack trace: ' . $e->getTraceAsString() );
		}
	}

	/**
	 * Bootstrap the feature.
	 *
	 * @param Container $container Service container instance.
	 */
	public function bootstrap( Container $container ): void {
		if ( self::$initialized ) {
			error_log( '⚠️ Profile_Bootstrap: Already initialized, skipping' );
			return;
		}

		error_log( '🚀 Profile_Bootstrap: Starting bootstrap process' );

		try {
			$this->container = $container;

			// Register services first
			$this->register_services( $container );
			error_log( '✅ Profile services registered' );

			// Register events
			$this->register_events( $container );
			error_log( '✅ Profile events registered' );

			// Initialize the feature
			$this->init();
			error_log( '✅ Profile feature initialized' );

			self::$initialized = true;
			error_log( '✨ Profile_Bootstrap: Bootstrap process completed' );
		} catch ( \Exception $e ) {
			error_log( '❌ Profile_Bootstrap: Error during bootstrap - ' . $e->getMessage() );
			error_log( 'Stack trace: ' . $e->getTraceAsString() );
		}
	}
}
