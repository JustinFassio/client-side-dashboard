import { createElement, useState } from '@wordpress/element';
import { LucideIcon } from 'lucide-react';
import { InjuryTracker } from '../InjuryTracker';
import { Injury } from '../InjuryTracker/types';
import { ProfileData, PROFILE_CONFIG } from '../../types/profile';
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

            createElement('div', { className: 'form-actions', key: 'actions' },
                createElement('button', { type: 'submit', className: 'save-button' }, 'Save Profile')
            )
        ])
    ]);
}; 