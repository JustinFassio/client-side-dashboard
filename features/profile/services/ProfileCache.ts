import { ProfileData } from './ProfileService';
import { Config } from '@dashboard/core/config';

/**
 * Cache service for profile data
 */
export class ProfileCache {
    private static CACHE_KEY = 'athlete_dashboard_profile';
    private static CACHE_DURATION = 5 * 60 * 1000; // 5 minutes

    /**
     * Get cached profile data
     */
    static getCachedProfile(): ProfileData | null {
        try {
            const cached = localStorage.getItem(this.CACHE_KEY);
            if (!cached) return null;

            const { data, timestamp } = JSON.parse(cached);
            const age = Date.now() - timestamp;

            // Check if cache is still valid
            if (age > this.CACHE_DURATION) {
                this.clearCache();
                return null;
            }

            Config.log('Retrieved profile from cache', 'profile');
            return data;
        } catch (error) {
            Config.log('Error reading from cache', 'profile');
            this.clearCache();
            return null;
        }
    }

    /**
     * Cache profile data
     */
    static cacheProfile(data: ProfileData): void {
        try {
            const cacheData = {
                data,
                timestamp: Date.now()
            };

            localStorage.setItem(this.CACHE_KEY, JSON.stringify(cacheData));
            Config.log('Profile cached successfully', 'profile');
        } catch (error) {
            Config.log('Error caching profile', 'profile');
        }
    }

    /**
     * Clear cached profile data
     */
    static clearCache(): void {
        try {
            localStorage.removeItem(this.CACHE_KEY);
            Config.log('Profile cache cleared', 'profile');
        } catch (error) {
            Config.log('Error clearing cache', 'profile');
        }
    }

    /**
     * Check if cache is valid
     */
    static isCacheValid(): boolean {
        try {
            const cached = localStorage.getItem(this.CACHE_KEY);
            if (!cached) return false;

            const { timestamp } = JSON.parse(cached);
            const age = Date.now() - timestamp;

            return age <= this.CACHE_DURATION;
        } catch {
            return false;
        }
    }
} 