import React, { createContext, useContext, useState, useEffect } from 'react';
import { ProfileData, PROFILE_CONFIG } from '../types/profile';
import { validateProfileField } from '../utils/validation';

interface ProfileContextValue {
    profile: ProfileData;
    updateProfile: (data: Partial<ProfileData>) => Promise<void>;
    isLoading: boolean;
    error: string | null;
}

const ProfileContext = createContext<ProfileContextValue | null>(null);

interface ProfileProviderProps {
    userId: number;
    children: React.ReactNode;
}

const DEFAULT_PROFILE: Partial<ProfileData> = {
    age: '',
    gender: '',
    height: '',
    weight: '',
    fitnessLevel: '',
    activityLevel: '',
    medicalConditions: [],
    exerciseLimitations: [],
    medications: '',
    injuries: []
};

export const ProfileProvider: React.FC<ProfileProviderProps> = ({ userId, children }) => {
    const [profile, setProfile] = useState<ProfileData>({ ...DEFAULT_PROFILE } as ProfileData);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const loadProfile = async () => {
            try {
                setIsLoading(true);
                setError(null);

                if (window.athleteDashboardData.debug) {
                    console.log('[ProfileContext] Loading profile for user:', userId);
                }

                const response = await fetch(`/wp-json/dashboard/v1/profile/${userId}`, {
                    headers: {
                        'X-WP-Nonce': window.athleteDashboardData.nonce
                    }
                });

                if (!response.ok) {
                    throw new Error(`Failed to load profile: ${response.status}`);
                }

                const data = await response.json();
                if (window.athleteDashboardData.debug) {
                    console.log('[ProfileContext] Profile loaded:', data);
                }

                // Merge loaded data with default values
                const mergedProfile = {
                    ...DEFAULT_PROFILE,
                    ...data
                };

                setProfile(mergedProfile as ProfileData);
            } catch (err) {
                const errorMessage = err instanceof Error ? err.message : 'An unexpected error occurred';
                if (window.athleteDashboardData.debug) {
                    console.error('[ProfileContext] Error loading profile:', errorMessage);
                }
                setError(errorMessage);
            } finally {
                setIsLoading(false);
            }
        };

        loadProfile();
    }, [userId]);

    const updateProfile = async (data: Partial<ProfileData>) => {
        try {
            setError(null);
            if (window.athleteDashboardData.debug) {
                console.log('[ProfileContext] Updating profile:', data);
            }

            // Only validate fields that are being updated
            const validationErrors: string[] = [];
            Object.entries(data).forEach(([field, value]) => {
                const error = validateProfileField(field as keyof ProfileData, value);
                if (error) {
                    validationErrors.push(`${field}: ${error}`);
                    if (window.athleteDashboardData.debug) {
                        console.warn(`[ProfileContext] Validation error for ${field}:`, error);
                    }
                }
            });

            if (validationErrors.length > 0) {
                throw new Error(validationErrors.join(', '));
            }

            const response = await fetch(`/wp-json/dashboard/v1/profile/${userId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.athleteDashboardData.nonce
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                throw new Error(`Failed to update profile: ${response.status}`);
            }

            const updatedData = await response.json();
            if (window.athleteDashboardData.debug) {
                console.log('[ProfileContext] Profile updated:', updatedData);
            }

            // Merge updated data with existing profile
            setProfile(prev => ({
                ...prev,
                ...updatedData
            }));
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Failed to update profile';
            if (window.athleteDashboardData.debug) {
                console.error('[ProfileContext] Error updating profile:', errorMessage);
            }
            setError(errorMessage);
            throw err;
        }
    };

    return (
        <ProfileContext.Provider value={{ profile, updateProfile, isLoading, error }}>
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