import React, { createContext, useContext } from 'react';
import { ProfileData } from '../types/profile';

export interface ProfileContextType {
    profileData: ProfileData | null;
    updateProfile: (data: Partial<ProfileData>) => Promise<void>;
    isLoading: boolean;
}

const ProfileContext = createContext<ProfileContextType | undefined>(undefined);

export const useProfile = () => {
    const context = useContext(ProfileContext);
    if (!context) {
        throw new Error('useProfile must be used within a ProfileProvider');
    }
    return context;
};

export const ProfileProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    // Implementation here...
    return <ProfileContext.Provider value={/* your value here */}>{children}</ProfileContext.Provider>;
}; 