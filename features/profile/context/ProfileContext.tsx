import React, { createContext, useContext, useState, useEffect, useMemo, useRef, useCallback } from 'react';
import { ProfileData } from '../types/profile';
import { useUser } from '../../user/context/UserContext';
import { ProfileService } from '../services/ProfileService';

interface ProfileContextValue {
    profile: ProfileData | null;
    updateProfile: (data: Partial<ProfileData>) => Promise<void>;
    refreshProfile: () => Promise<void>;
    isLoading: boolean;
    error: string | null;
}

const ProfileContext = createContext<ProfileContextValue | null>(null);

const DEFAULT_PROFILE: Partial<ProfileData> = {
    age: 0,
    gender: '',
    height: 0,
    weight: 0,
    medicalNotes: '',
    emergencyContactName: '',
    emergencyContactPhone: '',
    injuries: []
};

interface ProfileProviderProps {
    children: React.ReactNode;
}

// Create a singleton instance of ProfileService
const profileService = new ProfileService(
    window.athleteDashboardData?.apiUrl || '/wp-json',
    window.athleteDashboardData?.nonce || ''
);

export const ProfileProvider: React.FC<ProfileProviderProps> = ({ children }) => {
    const { user, isAuthenticated, isLoading: userLoading, refreshUser } = useUser();
    const [profile, setProfile] = useState<ProfileData | null>(null);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    // Use refs to prevent duplicate requests
    const isLoadingRef = useRef(false);
    const lastLoadTimeRef = useRef(0);
    const MIN_LOAD_INTERVAL = 1000; // Minimum time between loads in milliseconds

    // Debug log when user changes - throttled
    const lastUserLogRef = useRef(0);
    useEffect(() => {
        const now = Date.now();
        if (now - lastUserLogRef.current < 1000) return;
        lastUserLogRef.current = now;

        console.group('ProfileContext: User Change');
        console.log('Current user:', user);
        console.log('Is authenticated:', isAuthenticated);
        console.log('User loading:', userLoading);
        console.log('Has user ID:', !!user?.id);
        console.groupEnd();
    }, [user, isAuthenticated, userLoading]);

    const loadProfile = useCallback(async () => {
        // Don't attempt to load if user context is still loading
        if (userLoading) {
            console.log('ProfileContext: User context still loading, waiting...');
            return;
        }

        // Don't attempt to load if not authenticated
        if (!isAuthenticated || !user?.id) {
            console.log('ProfileContext: User not authenticated or missing ID, skipping profile load');
            setProfile(null);
            setIsLoading(false);
            return;
        }

        // Prevent duplicate loads
        const now = Date.now();
        if (isLoadingRef.current || (now - lastLoadTimeRef.current) < MIN_LOAD_INTERVAL) {
            console.log('ProfileContext: Skipping load - too soon or already in progress');
            return;
        }

        isLoadingRef.current = true;
        lastLoadTimeRef.current = now;

        try {
            console.group('ProfileContext: Loading Profile');
            console.log('Current user ID:', user.id);
            console.log('API URL:', window.athleteDashboardData?.apiUrl);
            console.log('Nonce present:', !!window.athleteDashboardData?.nonce);
            
            setIsLoading(true);
            setError(null);

            const profileData = await profileService.fetchProfile(user.id);
            console.log('Profile data received:', profileData);

            if (!profileData) {
                throw new Error('No profile data received from server');
            }

            // Merge with default values and user data
            const mergedProfile = {
                ...DEFAULT_PROFILE,
                ...profileData,
                // Ensure core user data is always in sync
                id: user.id,
                username: user.username,
                email: user.email,
                displayName: user.displayName,
                firstName: user.firstName,
                lastName: user.lastName
            };

            console.log('Merged profile data:', mergedProfile);
            setProfile(mergedProfile as ProfileData);
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'An unexpected error occurred';
            console.error('Error loading profile:', err);
            console.error('Error message:', errorMessage);
            setError(errorMessage);
            setProfile(null);
        } finally {
            setIsLoading(false);
            isLoadingRef.current = false;
            console.log('Profile load complete. Loading state set to false.');
            console.groupEnd();
        }
    }, [user, userLoading, isAuthenticated]);

    // Load profile when user changes - with debounce
    useEffect(() => {
        // Skip if user context is still loading
        if (userLoading) {
            console.log('ProfileContext: Waiting for user context to complete loading...');
            return;
        }

        // Clear profile if not authenticated
        if (!isAuthenticated || !user?.id) {
            console.log('ProfileContext: User not authenticated, clearing profile');
            setProfile(null);
            setIsLoading(false);
            return;
        }

        const timeoutId = setTimeout(() => {
            console.log('ProfileContext: User authenticated, initiating profile load:', user.id);
            loadProfile();
        }, 100); // Small delay to allow for any rapid user changes

        return () => clearTimeout(timeoutId);
    }, [user?.id, isAuthenticated, userLoading, loadProfile]);

    const refreshProfile = useCallback(async () => {
        console.log('ProfileContext: Refreshing profile...');
        await loadProfile();
    }, [loadProfile]);

    const updateProfile = useCallback(async (data: Partial<ProfileData>) => {
        if (!user?.id || !profile) {
            const error = 'User not authenticated or profile not loaded';
            console.error('ProfileContext: Update failed -', error);
            throw new Error(error);
        }

        try {
            console.group('ProfileContext: Updating Profile');
            console.log('Current profile:', profile);
            console.log('Update data:', data);
            setError(null);

            // Normalize email value
            const normalizedData = {
                ...data,
                // Convert empty strings to null, preserve undefined
                email: data.email === undefined ? undefined : (data.email?.trim() || null)
            };

            console.log('Normalized update data:', normalizedData);

            const updatedData = await profileService.updateProfile(user.id, normalizedData);
            console.log('Profile update successful:', updatedData);

            // Merge updated data with existing profile
            const mergedProfile = {
                ...profile,
                ...updatedData
            };

            console.log('Merged updated profile:', mergedProfile);
            setProfile(mergedProfile);
            
            // Refresh user data to ensure consistency
            console.log('Refreshing user data for consistency');
            await refreshUser();
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Failed to update profile';
            console.error('Error updating profile:', err);
            console.error('Error message:', errorMessage);
            setError(errorMessage);
            throw err;
        } finally {
            console.groupEnd();
        }
    }, [user?.id, profile, refreshUser]);

    const value = useMemo(() => ({
        profile,
        updateProfile,
        refreshProfile,
        isLoading,
        error
    }), [profile, isLoading, error, updateProfile, refreshProfile]);

    return (
        <ProfileContext.Provider value={value}>
            {children}
        </ProfileContext.Provider>
    );
};

export const useProfile = () => {
    const context = useContext(ProfileContext);
    if (!context) {
        throw new Error('useProfile must be used within a ProfileProvider');
    }
    return context;
}; 