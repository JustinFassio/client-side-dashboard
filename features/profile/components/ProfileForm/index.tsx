import { createElement, useState, useEffect } from '@wordpress/element';
import { LucideIcon } from 'lucide-react';
import { InjuryTracker } from '../InjuryTracker';
import { Injury } from '../InjuryTracker/types';
import { ProfileData, PROFILE_CONFIG } from '../../types/profile';
import { ProfileService } from '../../services/ProfileService';
import { Events } from '../../../../dashboard/core/events';
import { PROFILE_EVENTS } from '../../events';
import '../../assets/styles/components/ProfileForm.css';

interface Section {
    id: string;
    title: string;
    icon: LucideIcon;
    component?: React.ComponentType<any>;
}

interface ProfileFormProps {
    onSave: (data: Partial<ProfileData>) => void;
    sections: Section[];
}

export const ProfileForm = ({ onSave, sections }: ProfileFormProps) => {
    const [activeSection, setActiveSection] = useState(sections[0].id);
    const [formData, setFormData] = useState<Partial<ProfileData>>(PROFILE_CONFIG.validation.getDefaultProfile());
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        // Load initial profile data
        loadProfileData();

        // Subscribe to profile updates
        Events.on(PROFILE_EVENTS.PROFILE_UPDATED, handleProfileUpdated);
        Events.on(PROFILE_EVENTS.PROFILE_LOADING, handleProfileLoading);
        Events.on(PROFILE_EVENTS.PROFILE_UPDATE_FAILED, handleProfileUpdateFailed);

        return () => {
            Events.off(PROFILE_EVENTS.PROFILE_UPDATED, handleProfileUpdated);
            Events.off(PROFILE_EVENTS.PROFILE_LOADING, handleProfileLoading);
            Events.off(PROFILE_EVENTS.PROFILE_UPDATE_FAILED, handleProfileUpdateFailed);
        };
    }, []);

    const loadProfileData = async () => {
        try {
            const profile = await ProfileService.fetchProfile();
            setFormData(profile);
            setLoading(false);
        } catch (error) {
            console.error('Failed to load profile:', error);
            setLoading(false);
        }
    };

    const handleProfileUpdated = (profile: ProfileData) => {
        setFormData(profile);
        setLoading(false);
    };

    const handleProfileLoading = () => {
        setLoading(true);
    };

    const handleProfileUpdateFailed = () => {
        setLoading(false);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        // Validate form data
        const validation = PROFILE_CONFIG.validation.validateProfile(formData);
        if (!validation.isValid) {
            console.error('Form validation failed:', validation.errors);
            return;
        }

        onSave(formData);
    };

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
        const { name, value, type } = e.target;
        
        // Handle numeric fields
        if (type === 'number') {
            const numValue = value === '' ? null : Number(value);
            setFormData(prev => ({
                ...prev,
                [name]: numValue
            }));
            return;
        }
        
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
    };

    const handleInjuriesChange = (injuries: Injury[]) => {
        setFormData(prev => ({ ...prev, injuries }));
    };

    return createElement('div', { className: 'profile-form-container' }, [
        createElement('nav', { className: 'profile-sections', key: 'nav' },
            sections.map(section =>
                createElement('button', {
                    key: section.id,
                    className: `section-button ${activeSection === section.id ? 'active' : ''}`,
                    onClick: () => setActiveSection(section.id)
                }, [
                    createElement(section.icon, { size: 20, key: 'icon' }),
                    createElement('span', { key: 'text' }, section.title)
                ])
            )
        ),

        createElement('form', { onSubmit: handleSubmit, className: 'profile-form', key: 'form' }, [
            activeSection === 'basic' && createElement('div', { className: 'form-section', key: 'basic' }, [
                createElement('h2', { key: 'title' }, 'Basic Information'),
                ...Object.entries(PROFILE_CONFIG.fields)
                    .filter(([_, field]) => field.type !== 'select')
                    .map(([fieldName, field]) => 
                        createElement('div', { className: 'form-group', key: fieldName }, [
                            createElement('label', { htmlFor: fieldName, key: 'label' }, field.label),
                            createElement('input', {
                                key: 'input',
                                type: field.type,
                                id: fieldName,
                                name: fieldName,
                                value: formData[fieldName as keyof ProfileData] || '',
                                onChange: handleInputChange,
                                required: field.required,
                                min: field.validation?.min,
                                max: field.validation?.max
                            })
                        ])
                    ),
                ...Object.entries(PROFILE_CONFIG.fields)
                    .filter(([_, field]) => field.type === 'select')
                    .map(([fieldName, field]) =>
                        createElement('div', { className: 'form-group', key: fieldName }, [
                            createElement('label', { htmlFor: fieldName, key: 'label' }, field.label),
                            createElement('select', {
                                key: 'select',
                                id: fieldName,
                                name: fieldName,
                                value: formData[fieldName as keyof ProfileData] || '',
                                onChange: handleInputChange,
                                required: field.required,
                                className: 'form-control'
                            }, field.options?.map(option =>
                                createElement('option', {
                                    value: option.value,
                                    key: option.value
                                }, option.label)
                            ))
                        ])
                    )
            ]),

            activeSection === 'physical' && createElement('div', { className: 'form-section', key: 'physical' }, [
                createElement('h2', { key: 'title' }, 'Physical Information'),
                createElement('div', { className: 'form-group', key: 'height' }, [
                    createElement('label', { htmlFor: 'height', key: 'label' }, 'Height (cm)'),
                    createElement('input', {
                        key: 'input',
                        type: 'number',
                        id: 'height',
                        name: 'height',
                        value: formData.height || '',
                        onChange: handleInputChange,
                        min: '0',
                        max: '300'
                    })
                ]),
                createElement('div', { className: 'form-group', key: 'weight' }, [
                    createElement('label', { htmlFor: 'weight', key: 'label' }, 'Weight (kg)'),
                    createElement('input', {
                        key: 'input',
                        type: 'number',
                        id: 'weight',
                        name: 'weight',
                        value: formData.weight || '',
                        onChange: handleInputChange,
                        min: '0',
                        max: '500'
                    })
                ])
            ]),

            activeSection === 'medical' && createElement('div', { className: 'form-section', key: 'medical' }, [
                createElement('h2', { key: 'title' }, 'Medical Information'),
                createElement('div', { className: 'form-group', key: 'conditions' }, [
                    createElement('label', { htmlFor: 'medicalNotes', key: 'label' }, 'Medical Conditions'),
                    createElement('textarea', {
                        key: 'textarea',
                        id: 'medicalNotes',
                        name: 'medicalNotes',
                        value: formData.medicalNotes || '',
                        onChange: handleInputChange,
                        rows: 4,
                        placeholder: 'List any medical conditions that may affect your training...'
                    })
                ])
            ]),

            activeSection === 'injuries' && createElement('div', { className: 'form-section', key: 'injuries' }, [
                createElement('h2', { key: 'title' }, 'Injuries & Limitations'),
                createElement(InjuryTracker, {
                    key: 'tracker',
                    injuries: formData.injuries || [],
                    onChange: handleInjuriesChange
                })
            ]),

            activeSection === 'account' && createElement('div', { className: 'form-section', key: 'account' }, [
                createElement('h2', { key: 'title' }, 'Account Information'),
                createElement('p', { key: 'description', className: 'section-description' }, 
                    'Manage your account details and login information.'
                ),
                
                createElement('div', { className: 'account-fields', key: 'account-fields' }, [
                    createElement('div', { className: 'form-group', key: 'username' }, [
                        createElement('label', { htmlFor: 'username' }, 'Username'),
                        createElement('input', {
                            type: 'text',
                            id: 'username',
                            value: formData.username || '',
                            disabled: true,
                            className: 'readonly-field'
                        }),
                        createElement('small', { className: 'field-hint' }, 'Usernames cannot be changed.')
                    ]),
                    createElement('div', { className: 'form-group', key: 'firstName' }, [
                        createElement('label', { htmlFor: 'firstName' }, 'First Name'),
                        createElement('input', {
                            type: 'text',
                            id: 'firstName',
                            name: 'firstName',
                            value: formData.firstName || '',
                            onChange: handleInputChange
                        })
                    ]),
                    createElement('div', { className: 'form-group', key: 'lastName' }, [
                        createElement('label', { htmlFor: 'lastName' }, 'Last Name'),
                        createElement('input', {
                            type: 'text',
                            id: 'lastName',
                            name: 'lastName',
                            value: formData.lastName || '',
                            onChange: handleInputChange
                        })
                    ]),
                    createElement('div', { className: 'form-group', key: 'email' }, [
                        createElement('label', { htmlFor: 'email' }, 'Email'),
                        createElement('input', {
                            type: 'email',
                            id: 'email',
                            name: 'email',
                            value: formData.email || '',
                            onChange: handleInputChange
                        }),
                        createElement('small', { className: 'field-hint' }, 
                            'If you change this, an email will be sent to confirm the new address.'
                        )
                    ]),
                    createElement('div', { className: 'form-group', key: 'displayName' }, [
                        createElement('label', { htmlFor: 'displayName' }, 'Display Name'),
                        createElement('input', {
                            type: 'text',
                            id: 'displayName',
                            name: 'displayName',
                            value: formData.displayName || '',
                            onChange: handleInputChange
                        }),
                        createElement('small', { className: 'field-hint' }, 
                            'This is how your name will appear publicly.'
                        )
                    ])
                ])
            ])
        ])
    ]);
}; 