export interface DebugConfig {
    enabled: boolean;
    log_enabled: boolean;
}

export interface EnvironmentConfig {
    is_development: boolean;
    version: string;
}

export interface ProfileFieldConfig {
    enabled: boolean;
    required: boolean;
    label: string;
    type: string;
    validation?: {
        pattern?: RegExp;
        message?: string;
        min?: number;
        max?: number;
    };
}

export interface ProfileConfig {
    meta_prefix: string;
    fields: {
        [section: string]: {
            [field: string]: ProfileFieldConfig;
        };
    };
}

export interface DashboardConfig {
    debug: DebugConfig;
    environment: EnvironmentConfig;
    features: {
        profile: ProfileConfig;
    };
} 