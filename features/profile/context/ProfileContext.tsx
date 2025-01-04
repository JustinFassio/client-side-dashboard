import React, { createContext, useContext } from 'react';
import { FeatureContext } from '../../../dashboard/contracts/Feature';

interface ProfileContextValue {
    context: FeatureContext;
}

export const ProfileContext = createContext<ProfileContextValue | null>(null);

interface ProfileProviderProps {
    context: FeatureContext;
    children: React.ReactNode;
}

export const ProfileProvider: React.FC<ProfileProviderProps> = ({ context, children }) => {
    const value = { context };

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