import React, { createContext, useContext, useState, useEffect } from 'react';
import { ProfileData } from '../types/profile';

interface ProfileContextValue {
    profile: ProfileData;
    updateProfile: (data: Partial<ProfileData>) => void;
    isLoading: boolean;
    error: string | null;
}

const ProfileContext = createContext<ProfileContextValue | null>(null);

interface ProfileProviderProps {
    userId: number;
    children: React.ReactNode;
}

export const ProfileProvider: React.FC<ProfileProviderProps> = ({ userId, children }) => {
    const [profile, setProfile] = useState<ProfileData>({} as ProfileData);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const loadProfile = async () => {
            try {
                // TODO: Replace with actual API call
                const mockProfile: ProfileData = {
                    id: userId,
                    displayName: 'Test User',
                    email: 'test@example.com',
                    age: 25,
                    gender: 'male',
                    height: 180,
                    weight: 75,
                    fitnessLevel: 'intermediate',
                    activityLevel: 'moderately_active',
                    medicalConditions: [],
                    exerciseLimitations: [],
                    medications: '',
                    injuries: []
                };

                setProfile(mockProfile);
                setIsLoading(false);
            } catch (err) {
                setError(err instanceof Error ? err.message : 'Failed to load profile');
                setIsLoading(false);
            }
        };

        loadProfile();
    }, [userId]);

    const updateProfile = (data: Partial<ProfileData>) => {
        setProfile(prev => ({ ...prev, ...data }));
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