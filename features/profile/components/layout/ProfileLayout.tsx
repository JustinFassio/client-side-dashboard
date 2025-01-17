import React, { useState } from 'react';
import { FeatureContext } from '../../../../dashboard/contracts/Feature';
import { useProfile } from '../../context/ProfileContext';
import { useUser } from '../../../user/context/UserContext';
import { LoadingState } from '../../../../dashboard/components/LoadingState';
import { ErrorBoundary } from '../../../../dashboard/components/ErrorBoundary';
import { BasicSection } from '../form/sections/BasicSection';
import { MedicalSection } from '../form/sections/MedicalSection';
import { AccountSection } from '../form/sections/AccountSection';
import { PhysicalSection } from '../physical/PhysicalSection';
import { InjuryTracker } from '../InjuryTracker';
import { validateProfileField } from '../../utils/validation';
import { ProfileData, Injury } from '../../types/profile';
import './ProfileLayout.css';

interface ProfileLayoutProps {
    context: FeatureContext;
}

export const ProfileLayout: React.FC<ProfileLayoutProps> = ({
    context
}) => {
    const { user } = useUser();
    const { profile, updateProfile, refreshProfile, isLoading, error } = useProfile();
    const [localProfile, setLocalProfile] = useState<ProfileData | null>(profile);
    const [saveError, setSaveError] = useState<string | null>(null);
    const [isSaving, setIsSaving] = useState(false);

    // Update local profile when profile changes
    React.useEffect(() => {
        if (profile) {
            setLocalProfile(profile);
        }
    }, [profile]);

    // Refresh profile when user changes
    React.useEffect(() => {
        if (user?.id) {
            refreshProfile();
        }
    }, [user?.id, refreshProfile]);

    if (isLoading) {
        return <LoadingState label="Loading profile..." />;
    }

    if (error) {
        return (
            <div className="profile-error">
                <h3>Error Loading Profile</h3>
                <p>{error}</p>
                <button 
                    onClick={refreshProfile} 
                    className="retry-button"
                    disabled={isLoading}
                >
                    Retry
                </button>
            </div>
        );
    }

    if (!user || !profile || !localProfile) {
        return (
            <div className="profile-error">
                <h3>Profile Not Available</h3>
                <p>Unable to load profile information. Please ensure you are logged in.</p>
                <button 
                    onClick={refreshProfile}
                    className="retry-button"
                    disabled={isLoading}
                >
                    Retry
                </button>
            </div>
        );
    }

    const handleFieldChange = (name: string, value: any) => {
        setLocalProfile(prev => {
            if (!prev) return prev;
            return {
                ...prev,
                [name]: value
            };
        });
        setSaveError(null);
    };

    const handleInjuryChange = (injuries: Injury[]) => {
        setLocalProfile(prev => {
            if (!prev) return prev;
            return {
                ...prev,
                injuries
            };
        });
        setSaveError(null);
    };

    const handlePhysicalChange = (physicalData: any) => {
        setLocalProfile(prev => {
            if (!prev) return prev;
            return {
                ...prev,
                physical: physicalData
            };
        });
        setSaveError(null);
    };

    const handleSave = async () => {
        try {
            setSaveError(null);
            setIsSaving(true);

            console.group('Profile Save');
            console.log('Saving profile changes:', localProfile);

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
            console.groupEnd();
        }
    };

    return (
        <ErrorBoundary>
            <div className="profile-layout">
                <h1>Welcome, {profile.displayName || profile.username}</h1>
                <div className="profile-sections">
                    <BasicSection
                        data={localProfile}
                        onChange={handleFieldChange}
                    />
                    <MedicalSection
                        data={localProfile}
                        onChange={handleFieldChange}
                    />
                    <AccountSection
                        data={localProfile}
                        onChange={handleFieldChange}
                    />
                    <PhysicalSection
                        userId={user.id}
                    />
                    <InjuryTracker
                        injuries={localProfile.injuries || []}
                        onChange={handleInjuryChange}
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
            </div>
        </ErrorBoundary>
    );
}; 