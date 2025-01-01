import { ProfileData, ProfileError, ProfileErrorCode } from '../types/profile';

/**
 * Service for managing profile data
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
        // Ensure we have the required data
        if (!window.athleteDashboardData?.apiUrl) {
            console.error('athleteDashboardData.apiUrl is not defined!');
            throw new Error('API URL is not configured');
        }

        // Clean the base URL
        const baseUrl = window.athleteDashboardData.apiUrl.replace(/\/+$/, '');
        
        // If baseUrl already includes wp-json, don't add it again
        const hasWpJson = baseUrl.includes('/wp-json');
        const wpJsonBase = hasWpJson ? baseUrl : `${baseUrl}/wp-json`;
        
        // Construct the final endpoint
        const endpoint = `${wpJsonBase}/athlete-dashboard/v1/profile`;
        
        // Log the construction process
        console.group('Endpoint Construction');
        console.log('Base URL:', baseUrl);
        console.log('Has wp-json:', hasWpJson);
        console.log('WP JSON Base:', wpJsonBase);
        console.log('Final Endpoint:', endpoint);
        console.groupEnd();
        
        return endpoint;
    }

    private static endpoint = ProfileService.getEndpoint();

    /**
     * Test the API connection
     */
    public static async testConnection(): Promise<boolean> {
        try {
            this.debugEndpoint();
            const testEndpoint = this.endpoint + '/test';
            console.log('Testing connection to:', testEndpoint);

            const response = await fetch(testEndpoint, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.athleteDashboardData.nonce
                }
            });

            const data = await response.json();
            console.log('Test response:', data);
            return response.ok;
        } catch (error) {
            console.error('Connection test failed:', error);
            return false;
        }
    }

    /**
     * Fetch profile data for the current user
     */
    public static async fetchProfile(): Promise<ProfileData> {
        try {
            this.debugEndpoint();
            console.log('Fetching profile data...');
            
            const response = await fetch(this.endpoint, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.athleteDashboardData.nonce
                }
            });

            if (!response.ok) {
                console.error('Profile fetch failed:', {
                    status: response.status,
                    statusText: response.statusText,
                    url: response.url
                });
                throw await this.handleError(response);
            }

            const result = await response.json();
            console.log('Raw profile response:', result);

            // Handle the new response structure
            if (!result.success || !result.data?.profile) {
                throw new Error('Invalid profile data structure');
            }

            const profileData = result.data.profile;
            
            // Ensure age is a number
            if (profileData.age) {
                profileData.age = Number(profileData.age);
            }

            console.log('Profile data fetched successfully:', profileData);
            return profileData;
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
            console.log('Age value:', data.age, typeof data.age);
            
            // Ensure age is a number before sending
            const processedData = {
                ...data,
                age: data.age ? Number(data.age) : undefined
            };
            
            const response = await fetch(this.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.athleteDashboardData.nonce
                },
                body: JSON.stringify(processedData)
            });

            console.log('Response status:', response.status);
            console.log('Response headers:', Object.fromEntries(response.headers.entries()));

            if (!response.ok) {
                console.error('Profile update failed:', {
                    status: response.status,
                    statusText: response.statusText,
                    url: response.url
                });
                throw await this.handleError(response);
            }

            const result = await response.json();
            console.log('Raw update response:', result);

            // Handle the new response structure
            if (!result.success || !result.data?.profile) {
                throw new Error('Invalid profile data structure');
            }

            const updatedData = result.data.profile;
            
            // Ensure age is a number
            if (updatedData.age) {
                updatedData.age = Number(updatedData.age);
            }

            console.log('Profile updated successfully:', updatedData);
            console.groupEnd();
            return updatedData;
        } catch (error) {
            console.error('Error updating profile:', error);
            console.groupEnd();
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
            message: error.message || 'An unknown error occurred'
        };
    }

    /**
     * Type guard for ProfileError
     */
    private static isProfileError(error: any): error is ProfileError {
        return (
            error &&
            typeof error === 'object' &&
            'code' in error &&
            'message' in error
        );
    }

    /**
     * Get default profile data
     */
    public static getDefaultProfile(): ProfileData {
        return {
            userId: 0,
            firstName: '',
            lastName: '',
            age: 0,
            gender: 'prefer_not_to_say'
        };
    }
} 