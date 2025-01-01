import { ProfileData, ProfileError, ProfileErrorCode } from '../types/profile';
import { Events } from '../../../dashboard/core/events';
import { PROFILE_EVENTS } from '../types/events';

/**
 * Service for managing profile data and physical metrics
 */
export class ProfileService {
    private static debugEndpoint(): void {
        console.group('ProfileService Endpoint Debug');
        console.log('Raw API URL:', window.athleteDashboardData?.apiUrl || 'MISSING');
        console.log('Nonce:', window.athleteDashboardData?.nonce ? 'Present' : 'MISSING');
        console.log('Final Endpoint:', this.endpoint);
        console.groupEnd();
    }

    private static getEndpoint(): string {
        if (!window.athleteDashboardData?.apiUrl) {
            console.error('athleteDashboardData.apiUrl is not defined!');
            throw new Error('API URL is not configured');
        }

        const baseUrl = window.athleteDashboardData.apiUrl.replace(/\/+$/, '');
        const hasWpJson = baseUrl.includes('/wp-json');
        const wpJsonBase = hasWpJson ? baseUrl : `${baseUrl}/wp-json`;
        const endpoint = `${wpJsonBase}/athlete-dashboard/v1/profile`;
        
        return endpoint;
    }

    private static endpoint = ProfileService.getEndpoint();

    // Cache management
    private static cache = new Map<string, any>();
    private static cacheTimeout = 5 * 60 * 1000; // 5 minutes

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

        if (Date.now() - item.timestamp > this.cacheTimeout) {
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
     * Fetch profile data for the current user
     */
    public static async fetchProfile(): Promise<ProfileData> {
        try {
            const userId = window.athleteDashboardData?.userId;
            const cacheKey = this.getCacheKey(userId, 'profile');
            const cachedData = this.getCacheItem(cacheKey);

            if (cachedData) {
                return cachedData;
            }

            this.debugEndpoint();
            console.log('Fetching profile data...');
            
            const [userResponse, profileResponse] = await Promise.all([
                fetch(`${this.endpoint}/user`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': window.athleteDashboardData.nonce
                    }
                }),
                fetch(this.endpoint, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': window.athleteDashboardData.nonce
                    }
                })
            ]);

            if (!userResponse.ok || !profileResponse.ok) {
                throw await this.handleError(userResponse.ok ? profileResponse : userResponse);
            }

            const [userData, profileData] = await Promise.all([
                userResponse.json(),
                profileResponse.json()
            ]);

            const combinedData: ProfileData = {
                // WordPress core fields
                username: userData.data?.username || '',
                email: userData.data?.email || '',
                displayName: userData.data?.display_name || '',
                firstName: userData.data?.first_name || '',
                lastName: userData.data?.last_name || '',
                
                // Custom profile fields
                ...(profileData.data?.profile || {}),
                
                // Physical metrics
                height: profileData.data?.profile?.height ? Number(profileData.data.profile.height) : null,
                weight: profileData.data?.profile?.weight ? Number(profileData.data.profile.weight) : null,
                age: profileData.data?.profile?.age ? Number(profileData.data.profile.age) : null,
                
                // Activity metrics
                activityLevel: profileData.data?.profile?.activityLevel || null,
                fitnessLevel: profileData.data?.profile?.fitnessLevel || null,
                
                // Medical information
                medicalConditions: profileData.data?.profile?.medicalConditions || [],
                exerciseLimitations: profileData.data?.profile?.exerciseLimitations || [],
                medications: profileData.data?.profile?.medications || ''
            };

            this.setCacheItem(cacheKey, combinedData);
            return combinedData;
        } catch (error) {
            console.error('Error fetching profile:', error);
            throw this.normalizeError(error);
        }
    }

    /**
     * Update profile data for the current user
     */
    public static async updateProfile(data: Partial<ProfileData>): Promise<ProfileData> {
        try {
            this.debugEndpoint();
            console.group('Profile Update');
            console.log('Update data:', data);

            const { username, email, displayName, firstName, lastName, ...customFields } = data;
            
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
            console.log('Profile updated successfully:', updatedProfile);
            console.groupEnd();

            // Emit profile updated event
            Events.emit(PROFILE_EVENTS.UPDATE_SUCCESS, {
                type: PROFILE_EVENTS.UPDATE_SUCCESS,
                payload: updatedProfile
            });

            return updatedProfile;
        } catch (error) {
            console.error('Error updating profile:', error);
            console.groupEnd();

            // Emit error event
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