import React, { createContext, useContext, useState, useEffect, useMemo, useRef, useCallback } from 'react';
import { ProfileData, ComparisonResult } from '../types/profile';
import { useUser } from '../../user/context/UserContext';
import { ProfileService } from '../services/ProfileService';

interface ProfileContextValue {
    profile: ProfileData | null;
    updateProfile: (data: Partial<ProfileData>) => Promise<void>;
    refreshProfile: () => Promise<void>;
    isLoading: boolean;
    error: string | null;
    loadProfile: () => Promise<void>;
}

const ProfileContext = createContext<ProfileContextValue | null>(null);

const DEFAULT_PROFILE: Partial<ProfileData> = {
    age: 0,
    gender: '',
    medicalNotes: '',
    emergencyContactName: '',
    emergencyContactPhone: '',
    injuries: []
};

interface ProfileProviderProps {
    children: React.ReactNode;
}

export const ProfileProvider: React.FC<ProfileProviderProps> = ({ children }) => {
    const { user, isAuthenticated, isLoading: userLoading, refreshUser } = useUser();
    const [profile, setProfile] = useState<ProfileData | null>(null);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    // Initialize profile service
    const profileService = useMemo(() => {
        const apiUrl = window.athleteDashboardData?.apiUrl || '';
        const nonce = window.athleteDashboardData?.nonce || '';
        
        console.log('ProfileContext: Initializing service with:', {
            apiUrl: apiUrl ? '[SET]' : '[MISSING]',
            nonce: nonce ? '[SET]' : '[MISSING]'
        });
        
        return new ProfileService(apiUrl, nonce);
    }, []);

    // Monitor endpoint performance
    const endpointStats = useRef({
        totalRequests: 0,
        successfulRequests: 0,
        failedRequests: 0,
        lastError: null as string | null
    });

    // Log endpoint statistics after each profile load attempt
    useEffect(() => {
        if (!isLoading) {
            const stats = endpointStats.current;
            console.log('ProfileContext: Endpoint Statistics', {
                total: stats.totalRequests,
                successful: stats.successfulRequests,
                failed: stats.failedRequests,
                successRate: stats.totalRequests ? 
                    ((stats.successfulRequests / stats.totalRequests) * 100).toFixed(1) + '%' : 
                    'N/A',
                lastError: stats.lastError
            });
        }
    }, [isLoading]);

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
        endpointStats.current.totalRequests++;

        try {
            console.group('ProfileContext: Loading Profile');
            console.log('Current user ID:', user.id);
            console.log('API URL:', window.athleteDashboardData?.apiUrl);
            console.log('Nonce present:', !!window.athleteDashboardData?.nonce);
            
            setIsLoading(true);
            setError(null);

            const startTime = performance.now();
            const profileData = await profileService.fetchProfile(user.id);
            const endTime = performance.now();
            
            console.log('Profile data received:', profileData);
            console.log('Request duration:', Math.round(endTime - startTime), 'ms');

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
            endpointStats.current.successfulRequests++;
            endpointStats.current.lastError = null;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'An unexpected error occurred';
            console.error('Error loading profile:', err);
            console.error('Error message:', errorMessage);
            setError(errorMessage);
            setProfile(null);
            endpointStats.current.failedRequests++;
            endpointStats.current.lastError = errorMessage;
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

    const mergeProfileData = (newData: ProfileData) => {
        console.log('ProfileContext: Merging profile data', {
            current: profile,
            new: newData
        });

        // Preserve core user fields
        const mergedData = {
            ...profile,
            ...newData,
            // Ensure core user fields are not overwritten with empty values
            username: newData.username || profile?.username || '',
            email: newData.email || profile?.email || '',
            firstName: newData.firstName || profile?.firstName || '',
            lastName: newData.lastName || profile?.lastName || '',
            displayName: newData.displayName || profile?.displayName || '',
            roles: newData.roles || profile?.roles || []
        };

        console.log('ProfileContext: Merged result', mergedData);
        
        setProfile(mergedData);
    };

    const value = useMemo(() => ({
        profile,
        updateProfile,
        refreshProfile,
        isLoading,
        error,
        loadProfile
    }), [profile, isLoading, error, updateProfile, refreshProfile, loadProfile]);

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