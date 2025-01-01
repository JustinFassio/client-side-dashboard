import { ProfileData } from '../../types/profile';

interface ServiceConfig {
    nonce: string;
    apiUrl: string;
}

/**
 * Service for handling profile-related API calls to WordPress
 */
class ProfileService {
    private baseUrl: string = '';
    private nonce: string = '';

    configure(config: ServiceConfig): void {
        // Clean the API URL to prevent double slashes
        const cleanApiUrl = config.apiUrl.replace(/\/+$/, '');
        
        // If URL already includes wp-json, don't add it again
        const hasWpJson = cleanApiUrl.includes('/wp-json');
        const wpJsonBase = hasWpJson ? cleanApiUrl : `${cleanApiUrl}/wp-json`;
        
        this.baseUrl = `${wpJsonBase}/athlete-dashboard/v1`;
        this.nonce = config.nonce;
        
        // Log configuration
        console.group('ProfileService Configuration');
        console.log('Raw API URL:', config.apiUrl);
        console.log('Cleaned URL:', cleanApiUrl);
        console.log('Has wp-json:', hasWpJson);
        console.log('WP JSON Base:', wpJsonBase);
        console.log('Final Base URL:', this.baseUrl);
        console.groupEnd();
    }

    /**
     * Fetches the current user's profile data
     */
    async getCurrentProfile(): Promise<ProfileData> {
        if (!this.baseUrl) {
            throw new Error('ProfileService not configured');
        }

        const endpoint = `${this.baseUrl}/profile`;
        console.log('Fetching profile from:', endpoint);

        try {
            const response = await fetch(endpoint, {
                credentials: 'same-origin',
                headers: {
                    'X-WP-Nonce': this.nonce
                }
            });

            if (!response.ok) {
                console.error('Profile fetch failed:', {
                    status: response.status,
                    statusText: response.statusText
                });
                throw new Error('Failed to fetch profile data');
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

            console.log('Profile data processed:', profileData);
            return profileData;
        } catch (error) {
            console.error('Error fetching profile:', error);
            throw error;
        }
    }

    /**
     * Updates the current user's profile
     */
    async updateProfile(profileData: Partial<ProfileData>): Promise<ProfileData> {
        if (!this.baseUrl) {
            throw new Error('ProfileService not configured');
        }

        const endpoint = `${this.baseUrl}/profile`;
        console.group('Profile Update');
        console.log('Endpoint:', endpoint);
        console.log('Update data:', profileData);
        console.log('Age value:', profileData.age, typeof profileData.age);

        try {
            // Ensure age is a number before sending
            const processedData = {
                ...profileData,
                age: profileData.age ? Number(profileData.age) : undefined
            };

            console.log('Processed data:', processedData);
            
            const response = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.nonce
                },
                body: JSON.stringify(processedData)
            });

            console.log('Response status:', response.status);
            console.log('Response headers:', Object.fromEntries(response.headers.entries()));

            if (!response.ok) {
                console.error('Profile update failed:', {
                    status: response.status,
                    statusText: response.statusText
                });
                throw new Error('Failed to update profile');
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
            throw error;
        }
    }
}

export const profileService = new ProfileService(); 