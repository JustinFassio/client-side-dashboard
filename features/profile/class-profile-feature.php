<?php
/**
 * Profile Feature.
 *
 * @package AthleteDashboard\Features\Profile
 */

namespace AthleteDashboard\Features\Profile;

use AthleteDashboard\Features\Core\Contracts\Feature_Contract;
use AthleteDashboard\Features\Profile\API\Profile_Routes;
use AthleteDashboard\Features\Profile\API\CLI\Migration_Commands;
use AthleteDashboard\Features\Profile\Services\Profile_Service;
use AthleteDashboard\Features\Profile\Services\User_Service;
use AthleteDashboard\Features\Profile\Events\Profile_Updated;
use AthleteDashboard\Features\Profile\CLI\Physical_Data_Migration_Command;
use AthleteDashboard\Features\Profile\API\Endpoints\Physical\Physical_Get;
use AthleteDashboard\Features\Profile\API\Endpoints\Physical\Physical_Update;
use AthleteDashboard\Features\Profile\API\Endpoints\Physical\Physical_History;

/**
 * Class Profile_Feature
 *
 * Main class for the Profile feature.
 */
class Profile_Feature implements Feature_Contract {
	/**
	 * Profile routes instance.
	 *
	 * @var Profile_Routes
	 */
	private Profile_Routes $routes;

	/**
	 * Constructor.
	 *
	 * @param Profile_Routes $routes Profile routes instance.
	 */
	public function __construct( Profile_Routes $routes ) {
		$this->routes = $routes;
	}

	/**
	 * Initialize the feature.
	 *
	 * @return void
	 */
	public function init(): void {
		// Add REST API authentication
		add_filter(
			'rest_authentication_errors',
			function ( $result ) {
				error_log( '=== REST Authentication Check ===' );
				error_log( 'Authentication Result: ' . wp_json_encode( $result ) );

				// If there's already an error, return it
				if ( is_wp_error( $result ) ) {
					error_log( 'Authentication Error: ' . $result->get_error_message() );
					return $result;
				}

				error_log( 'Authentication Check Complete' );
				return $result;
			}
		);

		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
		// Initialize routes
		$this->routes->init();

		// Register CLI commands if WP-CLI is available
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command(
				'athlete-profile',
				new Migration_Commands( $this->routes )
			);

			// Register physical data migration command
			\WP_CLI::add_command(
				'athlete physical-data',
				Physical_Data_Migration_Command::class
			);
		}
	}

	public function register_endpoints(): void {
		error_log( '=== Athlete Dashboard [profile]: Starting Endpoint Registration ===' );
		error_log( 'Current User ID: ' . get_current_user_id() );
		error_log( 'Is User Logged In: ' . ( is_user_logged_in() ? 'Yes' : 'No' ) );

		error_log( 'Athlete Dashboard [profile]: Registering main profile endpoints' );
		// ... existing endpoint registrations ...

		error_log( 'Athlete Dashboard [profile]: Registering physical endpoints' );
		try {
			$physical_get = new Physical_Get();
			$physical_get->register();
			error_log( 'Physical_Get endpoint registered successfully' );

			$physical_update = new Physical_Update();
			$physical_update->register();
			error_log( 'Physical_Update endpoint registered successfully' );

			$physical_history = new Physical_History();
			$physical_history->register();
			error_log( 'Physical_History endpoint registered successfully' );
		} catch ( \Exception $e ) {
			error_log( 'Error registering physical endpoints: ' . $e->getMessage() );
		}

		error_log( 'Athlete Dashboard [profile]: REST routes registration complete' );
	}

	/**
	 * Get the public API exposed by this feature.
	 *
	 * @return array{
	 *     services?: array<class-string>,
	 *     events?: array<class-string>,
	 *     endpoints?: array<class-string>
	 * }
	 */
	public function get_public_api(): array {
		return array(
			'services'  => array(
				Profile_Service::class,
				User_Service::class,
			),
			'events'    => array(
				Profile_Updated::class,
			),
			'endpoints' => array(
				Profile_Routes::class,
			),
		);
	}

	/**
	 * Get feature dependencies.
	 *
	 * @return array<string, array{
	 *     events?: array<class-string>,
	 *     version?: string
	 * }>
	 */
	public function get_dependencies(): array {
		// Currently no dependencies, but we'll add User feature dependency
		// once it's migrated to the new contract system
		return array();
	}

	/**
	 * Get event subscriptions.
	 *
	 * @return array<class-string, array{
	 *     handler: string,
	 *     priority?: int
	 * }>
	 */
	public function get_event_subscriptions(): array {
		// Currently no event subscriptions
		return array();
	}
}
