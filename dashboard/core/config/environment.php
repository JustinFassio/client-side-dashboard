<?php
namespace AthleteDashboard\Core\Config;

/**
 * Environment configuration for the Athlete Dashboard
 * Handles environment-specific settings and configurations
 */
class Environment {
	/**
	 * Get environment settings
	 *
	 * @return array Environment configuration
	 */
	public static function get_settings(): array {
		return array(
			'mode'    => defined( 'WP_ENV' ) ? WP_ENV : 'production',
			'api_url' => rest_url( 'athlete-dashboard/v1' ),
		);
	}
}
