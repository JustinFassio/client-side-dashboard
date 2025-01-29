import { ProfileService } from '../../features/profile/services/ProfileService';
import { ApiClient } from '../../dashboard/services/api';

// Create a temporary instance for testing
const apiClient = new ApiClient({
    baseURL: window.athleteDashboardData.apiUrl,
    headers: {
        'X-WP-Nonce': window.athleteDashboardData.nonce
    }
});

const profileService = new ProfileService(apiClient, window.athleteDashboardData.nonce);

// Export for testing
window.profileService = profileService; 