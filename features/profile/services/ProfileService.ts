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
            
            // Fetch both WordPress user data and custom profile data
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
                console.error('Profile fetch failed:', {
                    userStatus: userResponse.status,
                    profileStatus: profileResponse.status
                });
                throw await this.handleError(userResponse.ok ? profileResponse : userResponse);
            }

            const [userData, profileData] = await Promise.all([
                userResponse.json(),
                profileResponse.json()
            ]);

            console.log('Raw responses:', { userData, profileData });

            // Combine WordPress user data with custom profile data
            const combinedData: ProfileData = {
                // WordPress core fields
                username: userData.data?.username || '',
                email: userData.data?.email || '',
                displayName: userData.data?.display_name || '',
                firstName: userData.data?.first_name || '',
                lastName: userData.data?.last_name || '',
                
                // Custom profile fields from profile response
                ...(profileData.data?.profile || {}),
                
                // Ensure numeric fields are properly typed
                age: profileData.data?.profile?.age ? Number(profileData.data.profile.age) : null,
                height: profileData.data?.profile?.height ? Number(profileData.data.profile.height) : null,
                weight: profileData.data?.profile?.weight ? Number(profileData.data.profile.weight) : null
            };

            console.log('Combined profile data:', combinedData);
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

            // Separate WordPress user fields from custom profile fields
            const { username, email, displayName, firstName, lastName, ...customFields } = data;
            
            // Update WordPress user data if any core fields are present
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
                    console.error('WordPress user update failed:', {
                        status: userResponse.status,
                        statusText: userResponse.statusText
                    });
                    throw await this.handleError(userResponse);
                }
            }

            // Update custom profile fields
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
                    console.error('Custom profile update failed:', {
                        status: profileResponse.status,
                        statusText: profileResponse.statusText
                    });
                    throw await this.handleError(profileResponse);
                }
            }

            // Fetch the updated profile data
            const updatedProfile = await this.fetchProfile();
            console.log('Profile updated successfully:', updatedProfile);
            console.groupEnd();
            return updatedProfile;
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
            // WordPress core fields
            username: '',
            email: '',
            displayName: '',
            firstName: '',
            lastName: '',
            
            // Custom profile fields
            userId: 0,
            age: null,
            gender: 'prefer_not_to_say',
            phone: '',
            dateOfBirth: '',
            height: undefined,
            weight: undefined,
            dominantSide: undefined,
            medicalClearance: false,
            medicalNotes: '',
            emergencyContactName: '',
            emergencyContactPhone: '',
            injuries: []
        };
    }
} 