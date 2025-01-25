import { DashboardData } from '../types/dashboard';
import { Feature, FeatureContext } from '../contracts/Feature';

/**
 * Validates the global dashboard data structure
 */
export function validateDashboardData(data: any): data is DashboardData {
    if (!data || typeof data !== 'object') {
        console.error('Dashboard data is not an object');
        return false;
    }

    const requiredFields: Array<keyof DashboardData> = ['apiUrl', 'nonce', 'userId'];
    const missingFields = requiredFields.filter(field => !(field in data));

    if (missingFields.length > 0) {
        console.error('Missing required dashboard data fields:', missingFields);
        return false;
    }

    if (data.feature && typeof data.feature === 'object') {
        if (typeof data.feature.name !== 'string' || typeof data.feature.label !== 'string') {
            console.error('Invalid feature data structure');
            return false;
        }
    }

    return true;
}

/**
 * Validates feature-specific data
 */
export function validateFeatureData<T>(
    feature: Feature,
    data: any,
    validator: (data: any) => data is T
): data is T {
    try {
        if (!validator(data)) {
            console.error(`Invalid data for feature "${feature.identifier}"`, data);
            return false;
        }
        return true;
    } catch (error) {
        console.error(`Error validating data for feature "${feature.identifier}":`, error);
        return false;
    }
}

/**
 * Validates the feature context
 */
export function validateFeatureContext(context: any): context is FeatureContext {
    if (!context || typeof context !== 'object') {
        console.error('Feature context is not an object');
        return false;
    }

    const requiredFields: Array<keyof FeatureContext> = ['apiUrl', 'nonce', 'dispatch'];
    const missingFields = requiredFields.filter(field => !(field in context));

    if (missingFields.length > 0) {
        console.error('Missing required feature context fields:', missingFields);
        return false;
    }

    return true;
} 