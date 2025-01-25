<?php
/**
 * Profile configuration class.
 *
 * @package AthleteDashboard\Features\Profile\Config
 */

namespace AthleteDashboard\Features\Profile\Config;

use AthleteDashboard\Core\Config\Debug;

/**
 * Class Config
 *
 * Manages configuration settings for the Profile feature.
 */
class Config {
	/**
	 * Debug context definitions for the Profile feature.
	 *
	 * Contexts are organized by functional area to maintain Feature-First principles:
	 * - general: Basic feature-level operations and initialization
	 * - api: REST API endpoint registration and routing
	 * - data: Data operations (CRUD) on profile data
	 * - auth: Authentication and authorization checks
	 * - events: Event-driven operations and hooks
	 *
	 * @var array
	 */
	private static $DEBUG_CONTEXTS = array(
		'general' => 'profile',          // Use for feature initialization and general operations
		'api'     => 'profile:api',      // Use for API endpoint registration and routing
		'data'    => 'profile:data',     // Use for profile data operations (get/update)
		'auth'    => 'profile:auth',     // Use for authentication and permission checks
		'events'  => 'profile:events',   // Use for WordPress actions and event handling
	);

	/**
	 * Get debug contexts for the Profile feature.
	 *
	 * Used to retrieve all available debug contexts for the Profile feature.
	 * This helps maintain consistency in debug logging across the feature.
	 *
	 * @return array Debug contexts mapping context keys to their full names.
	 */
	public static function get_debug_contexts(): array {
		return self::$DEBUG_CONTEXTS;
	}

	/**
	 * Get a specific debug context.
	 *
	 * Retrieves the full context name for a given context key.
	 * Falls back to the general profile context if the specific context isn't found.
	 *
	 * @param string $context Context key (e.g., 'api', 'data', 'auth').
	 * @return string Debug context string (e.g., 'profile:api', 'profile:data').
	 */
	public static function get_debug_context( string $context ): string {
		return self::$DEBUG_CONTEXTS[ $context ] ?? self::$DEBUG_CONTEXTS['general'];
	}

	/**
	 * Profile field configuration.
	 *
	 * @var array
	 */
	private static $FIELDS = array(
		'personal'    => array(
			'first_name' => array(
				'enabled'  => true,
				'required' => true,
				'type'     => 'text',
				'label'    => 'First Name',
			),
			'last_name'  => array(
				'enabled'  => true,
				'required' => true,
				'type'     => 'text',
				'label'    => 'Last Name',
			),
			'email'      => array(
				'enabled'  => true,
				'required' => true,
				'type'     => 'email',
				'label'    => 'Email',
			),
		),
		'medical'     => array(
			'height' => array(
				'enabled'  => true,
				'required' => false,
				'type'     => 'number',
				'label'    => 'Height (cm)',
			),
			'weight' => array(
				'enabled'  => true,
				'required' => false,
				'type'     => 'number',
				'label'    => 'Weight (kg)',
			),
		),
		'preferences' => array(
			'notifications' => array(
				'enabled'  => true,
				'required' => false,
				'type'     => 'boolean',
				'label'    => 'Enable Notifications',
			),
		),
	);

	/**
	 * Get profile settings.
	 *
	 * @return array Profile settings.
	 */
	public static function get_settings(): array {
		Debug::log( 'Getting profile settings' );
		return array(
			'fields' => self::$FIELDS,
		);
	}

	/**
	 * Get configuration for a specific field.
	 *
	 * @param string $section Section name.
	 * @param string $field Field name.
	 * @return array|null Field configuration or null if not found.
	 */
	public static function get_field_config( string $section, string $field ): ?array {
		Debug::log( "Getting field config for {$section}.{$field}" );
		return self::$FIELDS[ $section ][ $field ] ?? null;
	}

	/**
	 * Check if a field is enabled.
	 *
	 * @param string $section Section name.
	 * @param string $field Field name.
	 * @return bool True if field is enabled.
	 */
	public static function is_field_enabled( string $section, string $field ): bool {
		$config = self::get_field_config( $section, $field );
		return $config ? ( $config['enabled'] ?? false ) : false;
	}

	/**
	 * Get meta key for a field.
	 *
	 * @param string $field Field name.
	 * @return string Meta key.
	 */
	public static function get_meta_key( string $field ): string {
		return "_profile_{$field}";
	}
}
