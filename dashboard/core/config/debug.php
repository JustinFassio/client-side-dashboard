<?php
namespace AthleteDashboard\Core\Config;

/**
 * Debug configuration for the Athlete Dashboard
 * Handles debug-specific settings and logging configuration
 */
class Debug {
	/**
	 * Get debug settings
	 *
	 * @return array Debug configuration
	 */
	public static function get_settings(): array {
		$debug_enabled = apply_filters(
			'athlete_dashboard_debug_mode',
			defined( 'WP_DEBUG' ) && WP_DEBUG
		);

		return array(
			'enabled'         => $debug_enabled && current_user_can( 'administrator' ),
			'log_enabled'     => defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG,
			'display_enabled' => $debug_enabled && current_user_can( 'administrator' ),
		);
	}

	/**
	 * Check if debug mode is enabled
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		$settings = self::get_settings();
		return $settings['enabled'];
	}

	/**
	 * Log a debug message if debug mode is enabled
	 *
	 * @param string $message Message to log
	 * @param string $feature Feature name for context
	 */
	public static function log( string $message, string $feature = 'core' ): void {
		if ( ! self::is_enabled() || ! self::get_settings()['log_enabled'] ) {
			return;
		}
		error_log( sprintf( 'Athlete Dashboard [%s]: %s', $feature, $message ) );
	}
}
