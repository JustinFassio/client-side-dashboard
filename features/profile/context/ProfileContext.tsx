import React, { createContext, useContext, useState, useEffect, useMemo, useRef, useCallback } from 'react';
import { ProfileData, ComparisonResult } from '../types/profile';
import { useUser } from '../../user/context/UserContext';
import { ProfileService, UserProfile } from '../services/ProfileService';
import { ApiClient } from '../../../dashboard/services/api';

interface ProfileContextValue {
    loading: boolean;
    error: Error | null;
    profile?: UserProfile;
    loadUserProfile: (id: number) => Promise<void>;
    updateUserProfile: (id: number, data: Partial<UserProfile['data']>) => Promise<void>;
}

const ProfileContext = createContext<ProfileContextValue | undefined>(undefined);

const DEFAULT_PROFILE: Partial<ProfileData> = {
    age: 0,
    gender: '',
    medicalNotes: '',
    emergencyContactName: '',
    emergencyContactPhone: '',
    injuries: []
};

interface ProfileProviderProps {
    api: ApiClient;
    userId: number;
    children?: React.ReactNode;
}

export const ProfileProvider: React.FC<ProfileProviderProps> = ({ 
    api,
    userId, 
    children 
}) => {
    const { user, isAuthenticated, isLoading: userLoading, refreshUser } = useUser();
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<Error | null>(null);
    const [profile, setProfile] = useState<UserProfile>();

    const service = new ProfileService(api);

    const loadUserProfile = async (id: number) => {
        setLoading(true);
        setError(null);
        try {
            const data = await service.fetchUserProfile(id);
            setProfile(data);
        } catch (err) {
            setError(err instanceof Error ? err : new Error('Failed to load profile'));
            console.error('[ProfileContext] Error fetching user profile:', err);
        } finally {
            setLoading(false);
        }
    };

    const updateUserProfile = async (id: number, data: Partial<UserProfile['data']>) => {
        setLoading(true);
        setError(null);
        try {
            const updated = await service.updateUserProfile(id, data);
            setProfile(updated);
        } catch (err) {
            setError(err instanceof Error ? err : new Error('Failed to update profile'));
            console.error('[ProfileContext] Error updating user profile:', err);
        } finally {
            setLoading(false);
        }
    };

    // Load user's profile on mount
    useEffect(() => {
        loadUserProfile(userId);
    }, [userId]);

    return (
        <ProfileContext.Provider 
            value={{ 
                loading, 
                error,
                profile, 
                loadUserProfile,
                updateUserProfile
            }}
        >
            {children}
        </ProfileContext.Provider>
    );
};

export const useProfile = () => {
    const ctx = useContext(ProfileContext);
    if (!ctx) {
        throw new Error('useProfile must be used within a ProfileProvider');
    }
    return ctx;
}; 