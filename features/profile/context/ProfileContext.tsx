import React, { createContext, useContext, useEffect, useState, useCallback } from 'react';
import { ProfileService } from '../services/ProfileService';
import { ProfileData, ProfileState, ProfileError } from '../types/profile';

interface ProfileContextType extends ProfileState {
    updateProfile: (data: Partial<ProfileData>) => Promise<void>;
    resetError: () => void;
}

const ProfileContext = createContext<ProfileContextType | null>(null);

interface ProfileProviderProps {
    children: React.ReactNode;
}

export function ProfileProvider({ children }: ProfileProviderProps) {
    const [state, setState] = useState<ProfileState>({
        isComplete: false,
        isLoading: true,
        error: null,
        data: null
    });

    const loadProfile = useCallback(async () => {
        try {
            setState(prev => ({ ...prev, isLoading: true, error: null }));
            const profileData = await ProfileService.fetchProfile();
            
            // Check if profile is complete
            const isComplete = Boolean(
                profileData.firstName &&
                profileData.lastName &&
                profileData.age &&
                profileData.gender
            );

            setState({
                isComplete,
                isLoading: false,
                error: null,
                data: profileData
            });
        } catch (error) {
            console.error('Error loading profile:', error);
            setState(prev => ({
                ...prev,
                isLoading: false,
                error: error as ProfileError
            }));
        }
    }, []);

    const updateProfile = useCallback(async (data: Partial<ProfileData>) => {
        try {
            setState(prev => ({ ...prev, isLoading: true, error: null }));
            const updatedProfile = await ProfileService.updateProfile(data);
            
            // Check if profile is complete after update
            const isComplete = Boolean(
                updatedProfile.firstName &&
                updatedProfile.lastName &&
                updatedProfile.age &&
                updatedProfile.gender
            );

            setState({
                isComplete,
                isLoading: false,
                error: null,
                data: updatedProfile
            });
        } catch (error) {
            console.error('Error updating profile:', error);
            setState(prev => ({
                ...prev,
                isLoading: false,
                error: error as ProfileError
            }));
            throw error; // Re-throw to handle in the form
        }
    }, []);

    const resetError = useCallback(() => {
        setState(prev => ({ ...prev, error: null }));
    }, []);

    useEffect(() => {
        loadProfile();
    }, [loadProfile]);

    const value: ProfileContextType = {
        ...state,
        updateProfile,
        resetError
    };

    return (
        <ProfileContext.Provider value={value}>
            {children}
        </ProfileContext.Provider>
    );
}

export function useProfile(): ProfileContextType {
    const context = useContext(ProfileContext);
    if (!context) {
        throw new Error('useProfile must be used within a ProfileProvider');
    }
    return context;
}

interface ProfileErrorBoundaryProps {
    children: React.ReactNode;
    fallback: React.ReactNode;
}

interface ErrorBoundaryState {
    hasError: boolean;
    error: Error | null;
}

export class ProfileErrorBoundary extends React.Component<
    ProfileErrorBoundaryProps,
    ErrorBoundaryState
> {
    constructor(props: ProfileErrorBoundaryProps) {
        super(props);
        this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError(error: Error): ErrorBoundaryState {
        return { hasError: true, error };
    }

    componentDidCatch(error: Error, errorInfo: React.ErrorInfo) {
        console.error('Profile error boundary caught error:', error, errorInfo);
    }

    render() {
        if (this.state.hasError) {
            return this.props.fallback;
        }

        return this.props.children;
    }
} 