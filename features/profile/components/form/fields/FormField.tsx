import React from 'react';
import { ValidationResult } from '../../../types/validation';
import { ProfileData } from '../../../types/profile';

interface FormFieldProps {
    name: keyof ProfileData;
    label: string;
    type: 'text' | 'number' | 'email' | 'select' | 'tel';
    value: any;
    onChange: (name: string, value: any) => void;
    validation?: ValidationResult;
    options?: Array<{ value: string; label: string }>;
    required?: boolean;
    disabled?: boolean;
    min?: number;
    max?: number;
    isArray?: boolean;
}

export const FormField: React.FC<FormFieldProps> = ({
    name,
    label,
    type,
    value,
    onChange,
    validation,
    options,
    required,
    disabled,
    min,
    max,
    isArray = false
}) => {
    const handleChange = (event: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { value: newValue, type: inputType } = event.target;
        
        // Handle numeric fields
        if (inputType === 'number') {
            const numValue = newValue === '' ? undefined : Number(newValue);
            onChange(name, numValue);
            return;
        }

        // Handle array fields (like medical conditions)
        if (isArray && type === 'select') {
            const arrayValue = newValue ? [newValue] : [];
            onChange(name, arrayValue);
            return;
        }
        
        onChange(name, newValue);
    };

    const hasError = validation?.errors && validation.errors.length > 0;
    const fieldClassName = `form-field ${hasError ? 'has-error' : ''}`;

    // For array fields, get the first value if it exists
    const displayValue = isArray && Array.isArray(value) ? (value[0] || '') : (value || '');

    return (
        <div className={fieldClassName}>
            <label htmlFor={name}>{label}</label>
            
            {type === 'select' ? (
                <select
                    id={name}
                    name={name}
                    value={displayValue}
                    onChange={handleChange}
                    required={required}
                    disabled={disabled}
                >
                    <option value="">Select {label}</option>
                    {options?.map(option => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
            ) : (
                <input
                    type={type}
                    id={name}
                    name={name}
                    value={value || ''}
                    onChange={handleChange}
                    required={required}
                    disabled={disabled}
                    min={min}
                    max={max}
                />
            )}
            
            {hasError && validation.errors.map((error, index) => (
                <div key={index} className="field-error">
                    {error}
                </div>
            ))}
        </div>
    );
};

export default FormField; 