import React, { useState, useEffect } from 'react';
import { FeatureContext } from '../../../../dashboard/contracts/Feature';
import { useProfile } from '../../context/ProfileContext';
import { useUser } from '../../../user/context/UserContext';
import { LoadingState } from '../../../../dashboard/components/LoadingState';
import { ErrorBoundary } from '../../../../dashboard/components/ErrorBoundary';
import { Button } from '../../../../dashboard/components/Button';
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
    const [saveErrors, setSaveErrors] = useState<{
        basic?: string;
        medical?: string;
        account?: string;
        physical?: string;
        injuries?: string;
    }>({});
    const [isSaving, setIsSaving] = useState<{
        basic?: boolean;
        medical?: boolean;
        account?: boolean;
        physical?: boolean;
        injuries?: boolean;
    }>({});

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
                <Button
                    variant="secondary"
                    feature="profile"
                    onClick={refreshProfile}
                    disabled={isLoading}
                >
                    Retry
                </Button>
            </div>
        );
    }

    if (!user || !profile || !localProfile) {
        return (
            <div className="profile-error">
                <h3>Profile Not Available</h3>
                <p>Unable to load profile information. Please ensure you are logged in.</p>
                <Button
                    variant="secondary"
                    feature="profile"
                    onClick={refreshProfile}
                    disabled={isLoading}
                >
                    Retry
                </Button>
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
        // Clear any existing save errors when a field changes
        setSaveErrors(prev => ({
            ...prev,
            [name.includes('medical') ? 'medical' : 
             name.includes('account') ? 'account' :
             name.includes('physical') ? 'physical' :
             name.includes('injuries') ? 'injuries' : 'basic']: undefined
        }));
    };

    const handleInjuryChange = (injuries: Injury[]) => {
        setLocalProfile(prev => {
            if (!prev) return prev;
            return {
                ...prev,
                injuries
            };
        });
        setSaveErrors(prev => ({ ...prev, injuries: undefined }));
    };

    const handlePhysicalChange = (physicalData: any) => {
        setLocalProfile(prev => {
            if (!prev) return prev;
            return {
                ...prev,
                physical: physicalData
            };
        });
        setSaveErrors(prev => ({ ...prev, physical: undefined }));
    };

    const validateFields = (fields: string[]): string[] => {
        const validationErrors: string[] = [];
        fields.forEach(field => {
            const value = localProfile[field as keyof typeof localProfile];
            const error = validateProfileField(field as keyof ProfileData, value);
            if (error) {
                validationErrors.push(`${field}: ${error}`);
            }
        });
        return validationErrors;
    };

    const handleSectionSave = async (section: 'basic' | 'medical' | 'account' | 'physical' | 'injuries') => {
        try {
            setSaveErrors(prev => ({ ...prev, [section]: undefined }));
            setIsSaving(prev => ({ ...prev, [section]: true }));

            console.group(`Profile ${section} Save`);
            console.log(`Saving ${section} changes:`, localProfile);

            // Define fields for each section
            const sectionFields: Record<typeof section, string[]> = {
                basic: ['firstName', 'lastName', 'displayName', 'email'],
                medical: ['medicalConditions', 'exerciseLimitations', 'medications'],
                account: ['email', 'displayName', 'nickname'],
                physical: ['height', 'weight', 'age', 'gender'],
                injuries: ['injuries']
            };

            // Validate section fields
            const validationErrors = validateFields(sectionFields[section]);
            if (validationErrors.length > 0) {
                throw new Error(validationErrors.join(', '));
            }

            // Create a partial update with only the section's fields
            const sectionData = sectionFields[section].reduce((acc, field) => ({
                ...acc,
                [field]: localProfile[field as keyof ProfileData]
            }), {});

            await updateProfile(sectionData);

            if (context.debug) {
                console.log(`[ProfileLayout] ${section} saved successfully:`, sectionData);
            }
        } catch (err) {
            setSaveErrors(prev => ({
                ...prev,
                [section]: err instanceof Error ? err.message : `Failed to save ${section}`
            }));
            if (context.debug) {
                console.error(`[ProfileLayout] Error saving ${section}:`, err);
            }
        } finally {
            setIsSaving(prev => ({ ...prev, [section]: false }));
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
                        onSave={() => handleSectionSave('basic')}
                        isSaving={isSaving.basic}
                        error={saveErrors.basic}
                    />
                    <MedicalSection
                        data={localProfile}
                        onChange={handleFieldChange}
                        onSave={() => handleSectionSave('medical')}
                        isSaving={isSaving.medical}
                        error={saveErrors.medical}
                    />
                    <AccountSection
                        data={localProfile}
                        onChange={handleFieldChange}
                        onSave={() => handleSectionSave('account')}
                        isSaving={isSaving.account}
                        error={saveErrors.account}
                    />
                    <PhysicalSection
                        userId={user.id}
                        onSave={() => handleSectionSave('physical')}
                        isSaving={isSaving.physical}
                        error={saveErrors.physical}
                    />
                    <InjuryTracker
                        injuries={localProfile.injuries || []}
                        onChange={handleInjuryChange}
                        onSave={() => handleSectionSave('injuries')}
                        isSaving={isSaving.injuries}
                        error={saveErrors.injuries}
                    />
                </div>
            </div>
        </ErrorBoundary>
    );
}; 