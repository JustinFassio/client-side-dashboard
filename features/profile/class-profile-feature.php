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
		// Initialize routes
		$this->routes->init();

		// Register CLI commands if WP-CLI is available
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command(
				'athlete-profile',
				new Migration_Commands( $this->routes )
			);
		}
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
		return [
			'services' => [
				Profile_Service::class,
				User_Service::class,
			],
			'events' => [
				Profile_Updated::class,
			],
			'endpoints' => [
				Profile_Routes::class,
			],
		];
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
		return [];
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
		return [];
	}
}
