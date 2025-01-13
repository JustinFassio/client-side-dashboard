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
