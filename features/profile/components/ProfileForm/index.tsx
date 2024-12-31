import { createElement, useState } from '@wordpress/element';
import { LucideIcon } from 'lucide-react';
import { InjuryTracker } from '../InjuryTracker';
import { Injury } from '../InjuryTracker/types';
import './ProfileForm.css';

interface Section {
    id: string;
    title: string;
    icon: LucideIcon;
    component?: React.ComponentType<any>;
}

interface ProfileFormProps {
    onSave: (data: any) => void;
    sections: Section[];
}

export const ProfileForm = ({ onSave, sections }: ProfileFormProps) => {
    const [activeSection, setActiveSection] = useState(sections[0].id);
    const [formData, setFormData] = useState({
        age: '',
        gender: '',
        height: '',
        weight: '',
        medicalConditions: '',
        injuries: [] as Injury[]
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        onSave(formData);
    };

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
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
                createElement('div', { className: 'form-group', key: 'age' }, [
                    createElement('label', { htmlFor: 'age', key: 'label' }, 'Age'),
                    createElement('input', {
                        key: 'input',
                        type: 'number',
                        id: 'age',
                        name: 'age',
                        value: formData.age,
                        onChange: handleInputChange,
                        min: '0',
                        max: '120'
                    })
                ]),
                createElement('div', { className: 'form-group', key: 'gender' }, [
                    createElement('label', { htmlFor: 'gender', key: 'label' }, 'Gender'),
                    createElement('select', {
                        key: 'select',
                        id: 'gender',
                        name: 'gender',
                        value: formData.gender,
                        onChange: handleInputChange
                    }, [
                        createElement('option', { value: '', key: 'default' }, 'Select gender'),
                        createElement('option', { value: 'male', key: 'male' }, 'Male'),
                        createElement('option', { value: 'female', key: 'female' }, 'Female'),
                        createElement('option', { value: 'other', key: 'other' }, 'Other'),
                        createElement('option', { value: 'prefer-not-to-say', key: 'prefer' }, 'Prefer not to say')
                    ])
                ])
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
                        value: formData.height,
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
                        value: formData.weight,
                        onChange: handleInputChange,
                        min: '0',
                        max: '500'
                    })
                ])
            ]),

            activeSection === 'medical' && createElement('div', { className: 'form-section', key: 'medical' }, [
                createElement('h2', { key: 'title' }, 'Medical Information'),
                createElement('div', { className: 'form-group', key: 'conditions' }, [
                    createElement('label', { htmlFor: 'medicalConditions', key: 'label' }, 'Medical Conditions'),
                    createElement('textarea', {
                        key: 'textarea',
                        id: 'medicalConditions',
                        name: 'medicalConditions',
                        value: formData.medicalConditions,
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
                    injuries: formData.injuries,
                    onChange: handleInjuriesChange
                })
            ]),

            createElement('div', { className: 'form-actions', key: 'actions' },
                createElement('button', { type: 'submit', className: 'save-button' }, 'Save Profile')
            )
        ])
    ]);
}; 