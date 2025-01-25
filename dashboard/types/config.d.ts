// Core Debug Configuration
export interface DebugConfig {
    enabled: boolean;
    log_enabled: boolean;
    display_enabled: boolean;
}

// Core Environment Configuration
export interface EnvironmentConfig {
    environment: 'development' | 'staging' | 'production';
    is_development: boolean;
    is_staging: boolean;
    is_production: boolean;
    wp_version: string;
    theme_version: string;
}

// Profile Feature Configuration
export interface ProfileFieldConfig {
    enabled: boolean;
    required: boolean;
    type: 'text' | 'number' | 'select' | 'multiselect' | 'boolean' | 'textarea' | 'date';
    min?: number;
    max?: number;
    options?: string[];
    depends_on?: Record<string, boolean>;
}

export interface ProfileSectionConfig {
    [field: string]: ProfileFieldConfig;
}

export interface ProfileConfig {
    fields: {
        personal: ProfileSectionConfig;
        medical: ProfileSectionConfig;
        preferences: ProfileSectionConfig;
    };
    meta_prefix: string;
    events: {
        profile_updated: string;
        profile_loaded: string;
        profile_error: string;
    };
}

// Global Configuration Interface
declare global {
    interface Window {
        athleteDashboardData: {
            // WordPress data
            nonce: string;
            siteUrl: string;
            apiUrl: string;
            userId: number;

            // Core configurations
            environment: EnvironmentConfig;
            debug: DebugConfig;

            // Feature configurations
            features: {
                profile: ProfileConfig;
            };
        }
    }
}

// Export empty object to make this a module
export {}; 