<?php
namespace AthleteDashboard\Core;

use AthleteDashboard\Core\Config\Debug;

/**
 * Bridge between WordPress and the Athlete Dashboard
 * Handles communication between PHP and JavaScript layers
 */
class DashboardBridge {
	/**
	 * Get the current active feature
	 *
	 * @return string Current feature identifier
	 */
	public static function get_current_feature(): string {
		$feature = get_query_var( 'dashboard_feature', 'profile' );
		Debug::log( "Current feature: {$feature}", 'bridge' );
		return $feature;
	}

	/**
	 * Get feature-specific data for the current feature
	 *
	 * @param string $feature Feature identifier
	 * @return array Feature data
	 */
	public static function get_feature_data( string $feature = '' ): array {
		if ( empty( $feature ) ) {
			$feature = self::get_current_feature();
		}

		$data = array(
			'feature'  => $feature,
			'isActive' => true,
			'settings' => array(),
		);

		Debug::log( "Feature data prepared for: {$feature}", 'bridge' );
		return $data;
	}
}
