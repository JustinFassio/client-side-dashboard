import { createElement, useState, useEffect } from '@wordpress/element';
import { UserCircle } from 'lucide-react';
import { DisplayField } from '../../components/DisplayField';
import { CoreFields, CORE_FIELD_CONFIG, getDefaultCoreFields } from './config';
import { profileService } from '../../../../services/ProfileService';
import { Events } from '../../../../../../dashboard/core/events';
import { PROFILE_EVENTS } from '../../../../events';

export const CoreSection = () => {
    const [fields, setFields] = useState<CoreFields>(getDefaultCoreFields());
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        loadCoreFields();
    }, []);

    const loadCoreFields = async () => {
        try {
            setLoading(true);
            setError(null);
            const profile = await profileService.getCurrentProfile();
            
            setFields({
                username: {
                    value: profile.username || '',
                    lastUpdated: profile.lastUpdated || new Date().toISOString(),
                    isEditing: false
                },
                firstName: {
                    value: profile.firstName || '',
                    lastUpdated: profile.lastUpdated || new Date().toISOString(),
                    isEditing: false,
                    validation: CORE_FIELD_CONFIG.firstName.validation
                },
                lastName: {
                    value: profile.lastName || '',
                    lastUpdated: profile.lastUpdated || new Date().toISOString(),
                    isEditing: false,
                    validation: CORE_FIELD_CONFIG.lastName.validation
                },
                email: {
                    value: profile.email || '',
                    lastUpdated: profile.lastUpdated || new Date().toISOString(),
                    isEditing: false,
                    validation: CORE_FIELD_CONFIG.email.validation
                },
                displayName: {
                    value: profile.displayName || '',
                    lastUpdated: profile.lastUpdated || new Date().toISOString(),
                    isEditing: false,
                    validation: CORE_FIELD_CONFIG.displayName.validation
                }
            });
            setLoading(false);
        } catch (err) {
            setError('Failed to load profile data');
            setLoading(false);
            console.error('Error loading core fields:', err);
        }
    };

    const handleFieldEdit = (fieldName: keyof CoreFields) => {
        if (!CORE_FIELD_CONFIG[fieldName].editable) return;

        setFields(prev => ({
            ...prev,
            [fieldName]: {
                ...prev[fieldName],
                isEditing: true
            }
        }));
    };

    const handleFieldSave = async (fieldName: string, value: string) => {
        try {
            const updateData = { [fieldName]: value };
            await profileService.updateProfile(updateData);

            setFields(prev => ({
                ...prev,
                [fieldName]: {
                    ...prev[fieldName as keyof CoreFields],
                    value,
                    isEditing: false,
                    lastUpdated: new Date().toISOString()
                }
            }));

            Events.emit(PROFILE_EVENTS.PROFILE_UPDATED, {
                field: fieldName,
                value,
                timestamp: new Date().toISOString()
            });
        } catch (err) {
            console.error(`Error saving ${fieldName}:`, err);
            Events.emit(PROFILE_EVENTS.PROFILE_UPDATE_FAILED, {
                error: `Failed to update ${fieldName}`,
                field: fieldName
            });
        }
    };

    const handleFieldCancel = (fieldName: keyof CoreFields) => {
        setFields(prev => ({
            ...prev,
            [fieldName]: {
                ...prev[fieldName],
                isEditing: false
            }
        }));
    };

    if (loading) {
        return createElement('div', { className: 'core-section loading' }, [
            createElement(UserCircle, { 
                size: 24,
                className: 'section-icon',
                key: 'icon'
            }),
            createElement('span', { key: 'text' }, 'Loading profile data...')
        ]);
    }

    if (error) {
        return createElement('div', { className: 'core-section error' }, [
            createElement('span', { key: 'error' }, error),
            createElement('button', {
                key: 'retry',
                onClick: loadCoreFields,
                className: 'retry-button'
            }, 'Retry')
        ]);
    }

    return createElement('div', { className: 'core-section' }, [
        createElement('div', { className: 'fields-container', key: 'fields' },
            Object.entries(CORE_FIELD_CONFIG).map(([fieldName, config]) =>
                createElement(DisplayField, {
                    key: fieldName,
                    field: fields[fieldName as keyof CoreFields],
                    label: config.label,
                    name: fieldName,
                    onSave: handleFieldSave,
                    onCancel: handleFieldCancel
                })
            )
        )
    ]);
}; 