<?php
/**
 * @deprecated 1.0.0 Use AthleteDashboard\Features\Profile\Config\Config instead
 * This file is maintained for backward compatibility and will be removed in a future version.
 */

namespace AthleteDashboard\Features\Profile;

use AthleteDashboard\Features\Profile\Config\Config as NewConfig;

/**
 * @deprecated 1.0.0 Use AthleteDashboard\Features\Profile\Config\Config instead
 */
class Config {
	/**
	 * @deprecated 1.0.0 Use AthleteDashboard\Features\Profile\Config\Config::get_settings() instead
	 */
	public static function get_settings(): array {
		trigger_error(
			'Class ' . __CLASS__ . ' is deprecated. Use AthleteDashboard\Features\Profile\Config\Config instead.',
			E_USER_DEPRECATED
		);
		return NewConfig::get_settings();
	}

	/**
	 * @deprecated 1.0.0 Use AthleteDashboard\Features\Profile\Config\Config::get_field_config() instead
	 */
	public static function get_field_config( string $section, string $field ): ?array {
		trigger_error(
			'Class ' . __CLASS__ . ' is deprecated. Use AthleteDashboard\Features\Profile\Config\Config instead.',
			E_USER_DEPRECATED
		);
		return NewConfig::get_field_config( $section, $field );
	}

	/**
	 * @deprecated 1.0.0 Use AthleteDashboard\Features\Profile\Config\Config::is_field_enabled() instead
	 */
	public static function is_field_enabled( string $section, string $field ): bool {
		trigger_error(
			'Class ' . __CLASS__ . ' is deprecated. Use AthleteDashboard\Features\Profile\Config\Config instead.',
			E_USER_DEPRECATED
		);
		return NewConfig::is_field_enabled( $section, $field );
	}

	/**
	 * @deprecated 1.0.0 Use AthleteDashboard\Features\Profile\Config\Config::get_meta_key() instead
	 */
	public static function get_meta_key( string $field ): string {
		trigger_error(
			'Class ' . __CLASS__ . ' is deprecated. Use AthleteDashboard\Features\Profile\Config\Config instead.',
			E_USER_DEPRECATED
		);
		return NewConfig::get_meta_key( $field );
	}
}
