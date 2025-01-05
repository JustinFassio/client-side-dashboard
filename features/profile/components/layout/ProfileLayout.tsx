import React, { useState } from 'react';
import { FeatureContext } from '../../../../dashboard/contracts/Feature';
import { useProfile } from '../../context/ProfileContext';
import { BasicSection } from '../form/sections/BasicSection';
import { MedicalSection } from '../form/sections/MedicalSection';
import { PhysicalSection } from '../form/sections/PhysicalSection';
import { AccountSection } from '../form/sections/AccountSection';
import { InjuryTracker } from '../InjuryTracker';
import { validateProfileField } from '../../utils/validation';
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
    const [localProfile, setLocalProfile] = useState(profile);
    const [saveError, setSaveError] = useState<string | null>(null);
    const [isSaving, setIsSaving] = useState(false);

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
        setLocalProfile(prev => ({
            ...prev,
            [name]: value
        }));
        setSaveError(null);
    };

    const handleSave = async () => {
        try {
            setSaveError(null);
            setIsSaving(true);

            // Validate all fields
            const validationErrors: string[] = [];
            Object.entries(localProfile).forEach(([field, value]) => {
                const error = validateProfileField(field as keyof typeof localProfile, value);
                if (error) {
                    validationErrors.push(`${field}: ${error}`);
                }
            });

            if (validationErrors.length > 0) {
                throw new Error(validationErrors.join(', '));
            }

            await updateProfile(localProfile);

            if (context.debug) {
                console.log('[ProfileLayout] Profile saved successfully:', localProfile);
            }
        } catch (err) {
            setSaveError(err instanceof Error ? err.message : 'Failed to save profile');
            if (context.debug) {
                console.error('[ProfileLayout] Error saving profile:', err);
            }
        } finally {
            setIsSaving(false);
        }
    };

    return (
        <div className="profile-layout">
            <header className="profile-header">
                <h1>Profile Settings</h1>
                <p>Manage your athlete profile information</p>
            </header>

            <div className="profile-content">
                <AccountSection
                    data={localProfile}
                    onChange={handleFieldChange}
                />

                <BasicSection
                    data={localProfile}
                    onChange={handleFieldChange}
                />

                <MedicalSection
                    data={localProfile}
                    onChange={handleFieldChange}
                />

                <PhysicalSection
                    data={localProfile}
                    onChange={handleFieldChange}
                />

                <InjuryTracker
                    injuries={localProfile.injuries || []}
                    onChange={(injuries) => handleFieldChange('injuries', injuries)}
                />
            </div>

            {saveError && (
                <div className="save-error">
                    <p>{saveError}</p>
                </div>
            )}

            <div className="profile-actions">
                <button 
                    className="save-button"
                    onClick={handleSave}
                    disabled={isSaving}
                >
                    {isSaving ? 'Saving...' : 'Save Changes'}
                </button>
            </div>

            {context.debug && (
                <div className="debug-info">
                    <h3>Debug Information</h3>
                    <pre>
                        {JSON.stringify({ userId, profile: localProfile }, null, 2)}
                    </pre>
                </div>
            )}
        </div>
    );
}; 