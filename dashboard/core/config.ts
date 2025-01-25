import type { DebugConfig, EnvironmentConfig, ProfileConfig } from '../types/config';

/**
 * Configuration utility class for accessing dashboard settings
 */
export class Config {
    /**
     * Get debug configuration
     */
    static getDebug(): DebugConfig {
        return {
            enabled: window.athleteDashboardData.debug.enabled ?? false,
            log_enabled: window.athleteDashboardData.debug.log_enabled ?? false
        };
    }

    /**
     * Get environment configuration
     */
    static getEnvironment(): EnvironmentConfig {
        return window.athleteDashboardData.environment;
    }

    /**
     * Get profile feature configuration
     */
    static getProfileConfig(): ProfileConfig {
        return window.athleteDashboardData.features.profile;
    }

    /**
     * Check if we're in development mode
     */
    static isDevelopment(): boolean {
        return this.getEnvironment().is_development;
    }

    /**
     * Check if debug is enabled
     */
    static isDebugEnabled(): boolean {
        return this.getDebug().enabled;
    }

    /**
     * Log a debug message if debug is enabled
     */
    static log(message: string, feature: string = 'core'): void {
        if (this.isDebugEnabled() && this.getDebug().log_enabled) {
            console.log(`Athlete Dashboard [${feature}]:`, message);
        }
    }

    /**
     * Get a profile field configuration
     */
    static getProfileField(section: keyof ProfileConfig['fields'], field: string) {
        return this.getProfileConfig().fields[section]?.[field];
    }

    /**
     * Check if a profile field is enabled
     */
    static isProfileFieldEnabled(section: keyof ProfileConfig['fields'], field: string): boolean {
        return this.getProfileField(section, field)?.enabled ?? false;
    }

    /**
     * Get meta key for a profile field
     */
    static getProfileMetaKey(field: string): string {
        return `${this.getProfileConfig().meta_prefix}${field}`;
    }
} 