import { PROFILE_EVENTS } from '../types/events';

// Define field mapping interface
export interface FieldMapping {
    frontend: string;
    meta: string;
    isCore?: boolean;
}

export const ProfileConfig = {
    endpoints: {
        base: 'athlete-dashboard/v1/profile',
        test: 'athlete-dashboard/v1/profile/test',
        user: 'athlete-dashboard/v1/profile/user',
        full: 'athlete-dashboard/v1/profile/full',
        basic: 'athlete-dashboard/v1/profile/basic'
    },
    meta: {
        key: '_athlete_profile_data',
        prefix: 'athlete_',
        // Define explicit mapping between frontend and backend fields
        fields: {
            firstName: { frontend: 'firstName', meta: 'first_name', isCore: true },
            lastName: { frontend: 'lastName', meta: 'last_name', isCore: true },
            email: { frontend: 'email', meta: 'user_email', isCore: true },
            age: { frontend: 'age', meta: 'athlete_age' },
            gender: { frontend: 'gender', meta: 'athlete_gender' },
            height: { frontend: 'height', meta: 'athlete_height' },
            weight: { frontend: 'weight', meta: 'athlete_weight' },
            bio: { frontend: 'bio', meta: 'description', isCore: true },
            fitnessGoals: { frontend: 'fitnessGoals', meta: 'athlete_fitness_goals' },
            preferredWorkoutTypes: { frontend: 'preferredWorkoutTypes', meta: 'athlete_preferred_workout_types' }
        } as Record<string, FieldMapping>
    },
    events: PROFILE_EVENTS,
    validation: {
        minAge: 13,
        maxAge: 120,
        fields: {
            required: ['firstName', 'lastName'],
            optional: ['email', 'age', 'gender', 'height', 'weight', 'bio', 'fitnessGoals', 'preferredWorkoutTypes']
        }
    }
} as const;

// Type-safe helper for meta key generation
export const getMetaKey = (field: string): string => {
    const mapping = ProfileConfig.meta.fields[field];
    if (!mapping) {
        console.error(`No mapping found for field: ${field}`);
        return field;
    }
    return mapping.meta;
};

// Debug helper to verify URL construction
export const getFullEndpointUrl = (endpoint: keyof typeof ProfileConfig.endpoints): string => {
    const baseUrl = window.athleteDashboardData.apiUrl.replace(/\/?$/, '');
    const path = ProfileConfig.endpoints[endpoint].replace(/^\/+/, '');
    const url = `${baseUrl}/${path}`;
    
    // Enhanced debug logging
    console.group('URL Construction Debug');
    console.log('Input endpoint key:', endpoint);
    console.log('Base URL:', {
        raw: window.athleteDashboardData.apiUrl,
        cleaned: baseUrl,
        hasNamespace: baseUrl.includes('athlete-dashboard/v1'),
        hasWpJson: baseUrl.includes('wp-json')
    });
    console.log('Endpoint path:', {
        raw: ProfileConfig.endpoints[endpoint],
        cleaned: path,
        hasLeadingSlash: ProfileConfig.endpoints[endpoint].startsWith('/'),
        includesNamespace: path.includes('athlete-dashboard/v1')
    });
    console.log('Final URL:', {
        constructed: url,
        expectedPattern: '/wp-json/athlete-dashboard/v1/profile/user',
        matches: url.includes('/wp-json/athlete-dashboard/v1/profile/')
    });
    console.log('athleteDashboardData:', {
        ...window.athleteDashboardData,
        apiUrl: window.athleteDashboardData.apiUrl,
        nonce: window.athleteDashboardData.nonce ? '[PRESENT]' : '[MISSING]'
    });
    console.groupEnd();
    
    return url;
}; 