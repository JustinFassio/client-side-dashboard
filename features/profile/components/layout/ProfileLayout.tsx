import React from 'react';
import { FeatureContext } from '../../../../dashboard/contracts/Feature';
import { useProfile } from '../../context/ProfileContext';
import { BasicSection } from '../form/sections/BasicSection';
import { MedicalSection } from '../form/sections/MedicalSection';
import { PhysicalSection } from '../form/sections/PhysicalSection';
import { InjuryTracker } from '../InjuryTracker';
import './ProfileLayout.css';

interface ProfileLayoutProps {
    userId: number;
    context: FeatureContext;
}

export const ProfileLayout: React.FC<ProfileLayoutProps> = ({
    userId,
    context
}) => {
    const { profile, updateProfile, isLoading, error } = useProfile();

    if (isLoading) {
        return (
            <div className="profile-loading">
                <div className="loading-spinner" />
                <p>Loading profile...</p>
            </div>
        );
    }

    if (error) {
        return (
            <div className="profile-error">
                <h3>Error Loading Profile</h3>
                <p>{error}</p>
                <button onClick={() => window.location.reload()} className="retry-button">
                    Retry
                </button>
            </div>
        );
    }

    const handleFieldChange = (name: string, value: any) => {
        updateProfile({ ...profile, [name]: value });
    };

    return (
        <div className="profile-layout">
            <header className="profile-header">
                <h1>Profile Settings</h1>
                <p>Manage your athlete profile information</p>
            </header>

            <div className="profile-content">
                <BasicSection
                    data={profile}
                    onChange={handleFieldChange}
                />

                <MedicalSection
                    data={profile}
                    onChange={handleFieldChange}
                />

                <PhysicalSection
                    data={profile}
                    onChange={handleFieldChange}
                />

                <InjuryTracker
                    injuries={profile.injuries || []}
                    onChange={(injuries) => handleFieldChange('injuries', injuries)}
                />
            </div>

            {context.debug && (
                <div className="debug-info">
                    <h3>Debug Information</h3>
                    <pre>
                        {JSON.stringify({ userId, profile }, null, 2)}
                    </pre>
                </div>
            )}
        </div>
    );
}; 