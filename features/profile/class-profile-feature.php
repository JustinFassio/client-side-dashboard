<?php
/**
 * Profile Feature.
 *
 * @package AthleteDashboard\Features\Profile
 */

namespace AthleteDashboard\Features\Profile;

use AthleteDashboard\Features\Profile\API\Profile_Routes;
use AthleteDashboard\Features\Profile\API\CLI\Migration_Commands;

/**
 * Class Profile_Feature
 *
 * Main class for the Profile feature.
 */
class Profile_Feature {
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
}
