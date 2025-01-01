<?php
namespace AthleteDashboard\Core\Config;

/**
 * Environment configuration for the Athlete Dashboard
 * Handles environment detection and basic WordPress settings
 */
class Environment {
    /**
     * Possible environment types
     */
    public const ENV_DEVELOPMENT = 'development';
    public const ENV_STAGING = 'staging';
    public const ENV_PRODUCTION = 'production';

    /**
     * Get current environment settings
     * @return array Environment configuration
     */
    public static function get_settings(): array {
        return [
            'environment' => self::get_current_environment(),
            'is_development' => self::is_development(),
            'is_staging' => self::is_staging(),
            'is_production' => self::is_production(),
            'wp_version' => get_bloginfo('version'),
            'theme_version' => wp_get_theme()->get('Version')
        ];
    }

    /**
     * Get current environment type
     * @return string
     */
    public static function get_current_environment(): string {
        if (defined('WP_ENVIRONMENT_TYPE')) {
            return WP_ENVIRONMENT_TYPE;
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            return self::ENV_DEVELOPMENT;
        }

        return self::ENV_PRODUCTION;
    }

    /**
     * Check if current environment is development
     * @return bool
     */
    public static function is_development(): bool {
        return self::get_current_environment() === self::ENV_DEVELOPMENT;
    }

    /**
     * Check if current environment is staging
     * @return bool
     */
    public static function is_staging(): bool {
        return self::get_current_environment() === self::ENV_STAGING;
    }

    /**
     * Check if current environment is production
     * @return bool
     */
    public static function is_production(): bool {
        return self::get_current_environment() === self::ENV_PRODUCTION;
    }
} 