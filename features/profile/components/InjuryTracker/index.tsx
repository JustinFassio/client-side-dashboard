import { createElement, useState, useCallback } from '@wordpress/element';
import { PlusCircle, AlertCircle, XCircle } from 'lucide-react';
import { Injury, InjuryTrackerProps, PREDEFINED_INJURIES } from './types';
import './InjuryTracker.css';

export const InjuryTracker = ({ injuries, onChange, className = '' }: InjuryTrackerProps) => {
    const [newInjuryName, setNewInjuryName] = useState('');
    const [selectedPredefined, setSelectedPredefined] = useState('');

    const addInjury = useCallback((name: string, isCustom: boolean = false) => {
        const newInjury: Injury = {
            id: `injury_${Date.now()}`,
            name,
            details: '',
            dateAdded: new Date().toISOString(),
            isCustom,
            status: 'active',
            severity: 'medium'
        };

        onChange([...injuries, newInjury]);
        setNewInjuryName('');
        setSelectedPredefined('');
    }, [injuries, onChange]);

    const updateInjury = useCallback((id: string, updates: Partial<Injury>) => {
        const updatedInjuries = injuries.map(injury => 
            injury.id === id ? { ...injury, ...updates } : injury
        );
        onChange(updatedInjuries);
    }, [injuries, onChange]);

    const removeInjury = useCallback((id: string) => {
        onChange(injuries.filter(injury => injury.id !== id));
    }, [injuries, onChange]);

    return createElement('div', { className: `form-section ${className}` }, [
        // Add Injury Section
        createElement('div', { className: 'form-group', key: 'add-section' }, [
            createElement('label', { key: 'select-label' }, 'Select Common Injury'),
            createElement('select', {
                key: 'predefined',
                value: selectedPredefined,
                onChange: (e) => {
                    setSelectedPredefined(e.target.value);
                    if (e.target.value) addInjury(e.target.value, false);
                },
                className: 'form-control'
            }, [
                createElement('option', { value: '', key: 'default' }, 'Select common injury...'),
                ...PREDEFINED_INJURIES.map(injury => 
                    createElement('option', { value: injury.name, key: injury.name }, injury.name)
                )
            ])
        ]),

        // Custom Injury Input
        createElement('div', { className: 'form-group', key: 'custom' }, [
            createElement('label', { key: 'custom-label' }, 'Or Add Custom Injury'),
            createElement('div', { className: 'custom-injury-input', key: 'input-group' }, [
                createElement('div', { className: 'form-group' }, [
                    createElement('input', {
                        type: 'text',
                        value: newInjuryName,
                        onChange: (e) => setNewInjuryName(e.target.value),
                        placeholder: 'Enter custom injury...',
                        className: 'form-control'
                    })
                ]),
                createElement('button', {
                    onClick: () => newInjuryName && addInjury(newInjuryName, true),
                    disabled: !newInjuryName,
                    className: 'add-injury-btn'
                }, [
                    createElement(PlusCircle, { key: 'icon', size: 16 }),
                    'Add'
                ])
            ])
        ]),

        // Current Injuries List
        injuries.length > 0 && createElement('div', { className: 'injuries-list', key: 'list' }, 
            injuries.map(injury => 
                createElement('div', { key: injury.id, className: 'injury-item' }, [
                    createElement('div', { className: 'injury-header', key: 'header' }, [
                        createElement('h5', { key: 'name' }, injury.name),
                        createElement('button', {
                            key: 'remove',
                            onClick: () => removeInjury(injury.id),
                            className: 'remove-injury-btn'
                        }, createElement(XCircle, { size: 16 }))
                    ]),
                    createElement('div', { className: 'form-group', key: 'details' }, [
                        createElement('label', { key: 'details-label' }, 'Details'),
                        createElement('textarea', {
                            value: injury.details,
                            onChange: (e) => updateInjury(injury.id, { details: e.target.value }),
                            placeholder: 'Add details about this injury...',
                            className: 'form-control'
                        })
                    ]),
                    createElement('div', { className: 'injury-controls', key: 'controls' }, [
                        createElement('div', { className: 'form-group' }, [
                            createElement('label', { key: 'severity-label' }, 'Severity'),
                            createElement('select', {
                                value: injury.severity,
                                onChange: (e) => updateInjury(injury.id, { 
                                    severity: e.target.value as Injury['severity']
                                }),
                                className: 'form-control'
                            }, [
                                createElement('option', { value: 'low' }, 'Low'),
                                createElement('option', { value: 'medium' }, 'Medium'),
                                createElement('option', { value: 'high' }, 'High')
                            ])
                        ]),
                        createElement('div', { className: 'form-group' }, [
                            createElement('label', { key: 'status-label' }, 'Status'),
                            createElement('select', {
                                value: injury.status,
                                onChange: (e) => updateInjury(injury.id, {
                                    status: e.target.value as Injury['status']
                                }),
                                className: 'form-control'
                            }, [
                                createElement('option', { value: 'active' }, 'Active'),
                                createElement('option', { value: 'recovered' }, 'Recovered')
                            ])
                        ])
                    ])
                ])
            )
        )
    ]);
}; 