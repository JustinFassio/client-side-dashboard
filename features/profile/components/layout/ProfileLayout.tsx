import React, { useState, useCallback } from 'react';
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
import './ProfileLayout.css';

interface ProfileLayoutProps {
    context: FeatureContext;
    children?: React.ReactNode;
}

interface SectionState {
    isLoading: boolean;
    error: string | null;
    isDirty: boolean;
}

export const ProfileLayout: React.FC<ProfileLayoutProps> = ({
    context,
    children
}) => {
    const { user } = useUser();
    const { loading, error, profile, updateUserProfile } = useProfile();
    const [localData, setLocalData] = useState(profile?.data || {});
    const [isSaving, setIsSaving] = useState<Record<string, boolean>>({});
    const [saveErrors, setSaveErrors] = useState<Record<string, string>>({});
    const [sectionStates, setSectionStates] = useState<Record<string, SectionState>>({});

    // Global loading state
    if (loading) {
        return <LoadingState />;
    }

    // Global error state
    if (error) {
        return (
            <div className="error-state">
                <h3>Error Loading Profile</h3>
                <p>{error.message}</p>
                <button onClick={() => window.location.reload()}>Retry</button>
            </div>
        );
    }

    if (!profile) {
        return (
            <div className="empty-state">
                <h3>No Profile Data</h3>
                <p>Your profile information could not be found.</p>
            </div>
        );
    }

    const handleChange = useCallback((section: string, data: any) => {
        setLocalData(prev => ({
            ...prev,
            [section]: data
        }));
        setSectionStates(prev => ({
            ...prev,
            [section]: {
                ...prev[section],
                isDirty: true,
                error: null
            }
        }));
    }, []);

    const handleSave = useCallback(async (section: string) => {
        if (!profile) return;

        setIsSaving(prev => ({ ...prev, [section]: true }));
        setSectionStates(prev => ({
            ...prev,
            [section]: {
                ...prev[section],
                isLoading: true,
                error: null
            }
        }));

        try {
            await updateUserProfile(profile.user_id, {
                [section]: localData[section]
            });
            
            setSaveErrors(prev => {
                const newErrors = { ...prev };
                delete newErrors[section];
                return newErrors;
            });

            setSectionStates(prev => ({
                ...prev,
                [section]: {
                    ...prev[section],
                    isDirty: false,
                    error: null
                }
            }));
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Failed to save';
            setSaveErrors(prev => ({ 
                ...prev, 
                [section]: errorMessage
            }));
            setSectionStates(prev => ({
                ...prev,
                [section]: {
                    ...prev[section],
                    error: errorMessage
                }
            }));
        } finally {
            setIsSaving(prev => ({ ...prev, [section]: false }));
            setSectionStates(prev => ({
                ...prev,
                [section]: {
                    ...prev[section],
                    isLoading: false
                }
            }));
        }
    }, [profile, localData, updateUserProfile]);

    return (
        <ErrorBoundary>
            <div className="profile-layout">
                {children}
                <div className="profile-sections">
                    <BasicSection 
                        data={localData.basic}
                        onChange={(data) => handleChange('basic', data)}
                        onSave={() => handleSave('basic')}
                        isSaving={isSaving.basic}
                        error={saveErrors.basic}
                    />
                    <MedicalSection 
                        data={localData.medical}
                        onChange={(data) => handleChange('medical', data)}
                        onSave={() => handleSave('medical')}
                        isSaving={isSaving.medical}
                        error={saveErrors.medical}
                    />
                    <AccountSection 
                        data={localData.account}
                        onChange={(data) => handleChange('account', data)}
                        onSave={() => handleSave('account')}
                        isSaving={isSaving.account}
                        error={saveErrors.account}
                    />
                    <PhysicalSection 
                        userId={profile.user_id}
                        onSave={() => handleSave('physical')}
                        isSaving={isSaving.physical}
                        error={saveErrors.physical}
                    />
                    <InjuryTracker 
                        injuries={localData.injuries || []}
                        onChange={(injuries) => handleChange('injuries', injuries)}
                        onSave={() => handleSave('injuries')}
                        isSaving={isSaving.injuries}
                        error={saveErrors.injuries}
                    />
                </div>
            </div>
        </ErrorBoundary>
    );
}; 