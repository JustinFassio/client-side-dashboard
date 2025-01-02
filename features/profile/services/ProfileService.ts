import { ProfileData, ProfileError, ProfileErrorCode } from '../types/profile';
import { Events } from '../../../dashboard/core/events';
import { PROFILE_EVENTS } from '../types/events';

const DEBUG = window.athleteDashboardData?.debug || false;
const CACHE_TIMEOUT = 2 * 60 * 1000; // 2 minutes

/**
 * Service for managing profile data and physical metrics
 */
export class ProfileService {
    private static debugLog(message: string, data?: unknown): void {
        if (DEBUG) {
            console.log(`[ProfileService] ${message}`, data);
        }
    }

    private static getEndpoint(): string {
        if (!window.athleteDashboardData?.apiUrl) {
            throw new Error('API URL is not configured');
        }

        const baseUrl = window.athleteDashboardData.apiUrl.replace(/\/+$/, '');
        const hasWpJson = baseUrl.includes('/wp-json');
        const wpJsonBase = hasWpJson ? baseUrl : `${baseUrl}/wp-json`;
        return `${wpJsonBase}/athlete-dashboard/v1/profile`;
    }

    private static endpoint = ProfileService.getEndpoint();

    // Cache management
    private static cache = new Map<string, any>();
    private static loadingPromises = new Map<string, Promise<any>>();

    private static getCacheKey(userId: number, dataType: string): string {
        return `profile_${userId}_${dataType}`;
    }

    private static setCacheItem(key: string, data: any): void {
        this.cache.set(key, {
            data,
            timestamp: Date.now()
        });
    }

    private static getCacheItem(key: string): any | null {
        const item = this.cache.get(key);
        if (!item) return null;

        if (Date.now() - item.timestamp > CACHE_TIMEOUT) {
            this.cache.delete(key);
            return null;
        }

        return item.data;
    }

    private static clearCache(userId: number): void {
        const prefix = `profile_${userId}`;
        for (const key of this.cache.keys()) {
            if (key.startsWith(prefix)) {
                this.cache.delete(key);
            }
        }
    }

    /**
     * Fetch basic profile data for initial render
     */
    public static async fetchBasicProfile(): Promise<Partial<ProfileData>> {
        try {
            const userId = window.athleteDashboardData?.userId;
            const cacheKey = this.getCacheKey(userId, 'basic');
            const cachedData = this.getCacheItem(cacheKey);

            if (cachedData) {
                this.debugLog('Using cached basic profile data');
                return cachedData;
            }

            this.debugLog('Fetching basic profile data');
            
            const response = await fetch(`${this.endpoint}/basic`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.athleteDashboardData.nonce
                }
            });

            if (!response.ok) {
                throw await this.handleError(response);
            }

            const result = await response.json();
            const basicData = result.data;

            this.setCacheItem(cacheKey, basicData);
            this.debugLog('Basic profile data fetched and cached', basicData);
            
            // Start loading full profile data in the background
            this.prefetchFullProfile();

            return basicData;
        } catch (error) {
            this.debugLog('Error fetching basic profile:', error);
            throw this.normalizeError(error);
        }
    }

    /**
     * Prefetch full profile data in the background
     */
    private static async prefetchFullProfile(): Promise<void> {
        const userId = window.athleteDashboardData?.userId;
        const cacheKey = this.getCacheKey(userId, 'profile');

        // Check if already loading
        if (this.loadingPromises.has(cacheKey)) {
            return;
        }

        try {
            const loadingPromise = this.fetchFullProfile();
            this.loadingPromises.set(cacheKey, loadingPromise);
            await loadingPromise;
        } finally {
            this.loadingPromises.delete(cacheKey);
        }
    }

    /**
     * Fetch full profile data
     */
    private static async fetchFullProfile(): Promise<ProfileData> {
        try {
            const userId = window.athleteDashboardData?.userId;
            const cacheKey = this.getCacheKey(userId, 'profile');
            const cachedData = this.getCacheItem(cacheKey);

            if (cachedData) {
                this.debugLog('Using cached full profile data');
                return cachedData;
            }

            this.debugLog('Fetching full profile data');
            
            const response = await fetch(`${this.endpoint}/full`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.athleteDashboardData.nonce
                }
            });

            if (!response.ok) {
                throw await this.handleError(response);
            }

            const result = await response.json();
            const profileData = result.data;

            // Ensure numeric fields are properly typed
            const normalizedData: ProfileData = {
                ...profileData,
                height: profileData.height ? Number(profileData.height) : null,
                weight: profileData.weight ? Number(profileData.weight) : null,
                age: profileData.age ? Number(profileData.age) : null,
                medicalConditions: profileData.medicalConditions || [],
                exerciseLimitations: profileData.exerciseLimitations || [],
                medications: profileData.medications || ''
            };

            this.setCacheItem(cacheKey, normalizedData);
            this.debugLog('Full profile data fetched and cached', normalizedData);
            return normalizedData;
        } catch (error) {
            this.debugLog('Error fetching full profile:', error);
            throw this.normalizeError(error);
        }
    }

    /**
     * Get complete profile data, waiting for full data if necessary
     */
    public static async fetchProfile(): Promise<ProfileData> {
        const userId = window.athleteDashboardData?.userId;
        const cacheKey = this.getCacheKey(userId, 'profile');

        // If already loading, wait for that promise
        const loadingPromise = this.loadingPromises.get(cacheKey);
        if (loadingPromise) {
            return loadingPromise;
        }

        // Otherwise, load full profile
        return this.fetchFullProfile();
    }

    /**
     * Update profile data for the current user
     */
    public static async updateProfile(data: Partial<ProfileData>): Promise<ProfileData> {
        try {
            this.debugLog('Updating profile data', data);

            const { username, email, displayName, firstName, lastName, ...customFields } = data;
            
            // Update user data if provided
            if (email || displayName || firstName || lastName) {
                const userResponse = await fetch(`${this.endpoint}/user`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': window.athleteDashboardData.nonce
                    },
                    body: JSON.stringify({
                        email,
                        display_name: displayName,
                        first_name: firstName,
                        last_name: lastName
                    })
                });

                if (!userResponse.ok) {
                    throw await this.handleError(userResponse);
                }
            }

            // Update custom fields if provided
            if (Object.keys(customFields).length > 0) {
                const profileResponse = await fetch(this.endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': window.athleteDashboardData.nonce
                    },
                    body: JSON.stringify(customFields)
                });

                if (!profileResponse.ok) {
                    throw await this.handleError(profileResponse);
                }
            }

            // Clear cache after successful update
            this.clearCache(window.athleteDashboardData.userId);

            // Fetch and return the updated profile
            const updatedProfile = await this.fetchProfile();
            this.debugLog('Profile updated successfully');

            Events.emit(PROFILE_EVENTS.UPDATE_SUCCESS, {
                type: PROFILE_EVENTS.UPDATE_SUCCESS,
                payload: updatedProfile
            });

            return updatedProfile;
        } catch (error) {
            this.debugLog('Error updating profile:', error);

            Events.emit(PROFILE_EVENTS.UPDATE_ERROR, {
                type: PROFILE_EVENTS.UPDATE_ERROR,
                error: this.normalizeError(error)
            });

            throw this.normalizeError(error);
        }
    }

    /**
     * Handle API error responses
     */
    private static async handleError(response: Response): Promise<ProfileError> {
        let errorData;
        try {
            errorData = await response.json();
        } catch {
            errorData = { message: 'An unknown error occurred' };
        }

        const error: ProfileError = {
            code: this.getErrorCode(response.status),
            message: errorData.message || 'An unknown error occurred',
            details: errorData.details
        };

        return error;
    }

    /**
     * Map HTTP status codes to error codes
     */
    private static getErrorCode(status: number): ProfileErrorCode {
        switch (status) {
            case 400:
                return 'VALIDATION_ERROR';
            case 401:
            case 403:
                return 'AUTH_ERROR';
            case 404:
                return 'NETWORK_ERROR';
            default:
                return 'SERVER_ERROR';
        }
    }

    /**
     * Normalize errors to ProfileError format
     */
    private static normalizeError(error: any): ProfileError {
        if (this.isProfileError(error)) {
            return error;
        }

        return {
            code: 'SERVER_ERROR',
            message: error instanceof Error ? error.message : 'An unknown error occurred'
        };
    }

    private static isProfileError(error: any): error is ProfileError {
        return error && typeof error === 'object' && 'code' in error && 'message' in error;
    }

    static getDefaultProfile(): ProfileData {
        return {
            username: '',
            email: '',
            displayName: '',
            firstName: '',
            lastName: '',
            age: 0,
            gender: 'prefer_not_to_say',
            height: 0,
            weight: 0,
            fitnessLevel: 'beginner',
            activityLevel: 'sedentary',
            medicalConditions: [],
            exerciseLimitations: [],
            medications: ''
        };
    }
} 