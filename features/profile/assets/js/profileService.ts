import { ProfileData } from '../../events';

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
        this.baseUrl = `${config.apiUrl}/athlete-dashboard/v1`;
        this.nonce = config.nonce;
    }

    /**
     * Fetches the current user's profile data
     */
    async getCurrentProfile(): Promise<ProfileData> {
        if (!this.baseUrl) {
            throw new Error('ProfileService not configured');
        }

        const response = await fetch(`${this.baseUrl}/profile`, {
            credentials: 'same-origin',
            headers: {
                'X-WP-Nonce': this.nonce
            }
        });

        if (!response.ok) {
            throw new Error('Failed to fetch profile data');
        }

        const data = await response.json();
        return data;
    }

    /**
     * Updates the current user's profile
     */
    async updateProfile(profileData: Partial<ProfileData>): Promise<ProfileData> {
        if (!this.baseUrl) {
            throw new Error('ProfileService not configured');
        }

        const response = await fetch(`${this.baseUrl}/profile`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': this.nonce
            },
            body: JSON.stringify(profileData)
        });

        if (!response.ok) {
            throw new Error('Failed to update profile');
        }

        const data = await response.json();
        return data;
    }
}

export const profileService = new ProfileService(); 