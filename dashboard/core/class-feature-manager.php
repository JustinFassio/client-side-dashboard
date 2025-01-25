<?php
namespace AthleteDashboard\Core;

use AthleteDashboard\Core\Config\Debug;

/**
 * Feature Manager for the Athlete Dashboard
 * Handles feature registration, activation, and management
 */
class FeatureManager {
	/**
	 * Registered features
	 *
	 * @var array
	 */
	private static $features = array();

	/**
	 * Register a new feature
	 *
	 * @param string $feature_id Unique feature identifier
	 * @param array  $config Feature configuration
	 * @return bool Success status
	 */
	public static function register_feature( string $feature_id, array $config ): bool {
		if ( isset( self::$features[ $feature_id ] ) ) {
			Debug::log( "Feature already registered: {$feature_id}", 'manager' );
			return false;
		}

		self::$features[ $feature_id ] = array_merge(
			array(
				'id'           => $feature_id,
				'active'       => true,
				'dependencies' => array(),
				'settings'     => array(),
			),
			$config
		);

		Debug::log( "Feature registered: {$feature_id}", 'manager' );
		return true;
	}

	/**
	 * Get a registered feature's configuration
	 *
	 * @param string $feature_id Feature identifier
	 * @return array|null Feature configuration or null if not found
	 */
	public static function get_feature( string $feature_id ): ?array {
		if ( ! isset( self::$features[ $feature_id ] ) ) {
			Debug::log( "Feature not found: {$feature_id}", 'manager' );
			return null;
		}

		return self::$features[ $feature_id ];
	}

	/**
	 * Check if a feature is registered and active
	 *
	 * @param string $feature_id Feature identifier
	 * @return bool Feature status
	 */
	public static function is_feature_active( string $feature_id ): bool {
		$feature = self::get_feature( $feature_id );
		return $feature && $feature['active'];
	}

	/**
	 * Get all registered features
	 *
	 * @return array List of registered features
	 */
	public static function get_features(): array {
		return self::$features;
	}
}
