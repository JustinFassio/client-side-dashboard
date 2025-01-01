import { createElement, useState } from '@wordpress/element';
import { Edit2, X, Check } from 'lucide-react';
import { ProfileField } from '../../../types/profile-display';

interface DisplayFieldProps<T> {
    field: ProfileField<T>;
    label: string;
    name: string;
    onSave: (name: string, value: T) => void;
    onCancel: (name: string) => void;
}

export const DisplayField = <T,>({ 
    field, 
    label, 
    name, 
    onSave, 
    onCancel 
}: DisplayFieldProps<T>) => {
    const [editValue, setEditValue] = useState<T>(field.value);
    const [error, setError] = useState<string | null>(null);

    const handleEdit = () => {
        setEditValue(field.value);
        setError(null);
    };

    const handleCancel = () => {
        onCancel(name);
        setError(null);
    };

    const handleSave = () => {
        // Validate if validation function exists
        if (field.validation) {
            const isValid = typeof field.validation === 'function'
                ? field.validation(editValue)
                : field.validation.test(String(editValue));

            if (!isValid) {
                setError('Invalid value');
                return;
            }
        }

        onSave(name, editValue);
        setError(null);
    };

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.type === 'number' 
            ? Number(e.target.value) 
            : e.target.value;
        setEditValue(value as T);
        setError(null);
    };

    const displayValue = field.displayFormat 
        ? field.displayFormat(field.value)
        : String(field.value);

    return createElement('div', { className: 'profile-field' }, [
        createElement('label', { key: 'label' }, [
            label,
            createElement('span', { 
                key: 'updated',
                className: 'field-updated'
            }, `Last updated: ${new Date(field.lastUpdated).toLocaleDateString()}`)
        ]),

        field.isEditing
            ? createElement('div', { key: 'edit', className: 'field-edit' }, [
                createElement('input', {
                    key: 'input',
                    type: typeof field.value === 'number' ? 'number' : 'text',
                    value: editValue,
                    onChange: handleChange,
                    className: error ? 'has-error' : ''
                }),
                createElement('div', { key: 'actions', className: 'field-actions' }, [
                    createElement('button', {
                        key: 'save',
                        onClick: handleSave,
                        className: 'action-button save',
                        title: 'Save'
                    }, createElement(Check, { size: 16 })),
                    createElement('button', {
                        key: 'cancel',
                        onClick: handleCancel,
                        className: 'action-button cancel',
                        title: 'Cancel'
                    }, createElement(X, { size: 16 }))
                ]),
                error && createElement('div', {
                    key: 'error',
                    className: 'field-error'
                }, error)
            ])
            : createElement('div', { key: 'display', className: 'field-display' }, [
                createElement('span', { 
                    key: 'value',
                    className: 'field-value'
                }, displayValue),
                createElement('button', {
                    key: 'edit',
                    onClick: handleEdit,
                    className: 'action-button edit',
                    title: 'Edit'
                }, createElement(Edit2, { size: 16 }))
            ])
    ]);
}; 