import React, { useState, useCallback } from 'react';
import { useProfile } from '../context/ProfileContext';
import { ProfileData, PROFILE_CONFIG, ProfileError } from '../types/profile';
import { SaveAlert } from './SaveAlert';
import { Events } from '../../../dashboard/core/events';
import { PROFILE_EVENTS } from '../events';

export function BasicProfileForm() {
    const { profile, updateProfile, isLoading } = useProfile();
    const [formData, setFormData] = useState<Partial<ProfileData>>(profile || PROFILE_CONFIG.validation.getDefaultProfile());
    const [saveSuccess, setSaveSuccess] = useState(false);
    const [saveError, setSaveError] = useState<string | null>(null);
    const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});

    const validateField = useCallback((name: keyof ProfileData, value: any): string[] => {
        const field = PROFILE_CONFIG.fields[name];
        if (!field) return [];

        const errors: string[] = [];
        
        if (field.required && (value === undefined || value === '')) {
            errors.push(`${field.label} is required`);
        }

        if (value !== undefined && value !== '') {
            if (field.validation?.min !== undefined && value < field.validation.min) {
                errors.push(`${field.label} must be at least ${field.validation.min}`);
            }
            if (field.validation?.max !== undefined && value > field.validation.max) {
                errors.push(`${field.label} must be no more than ${field.validation.max}`);
            }
            if (field.validation?.pattern && !field.validation.pattern.test(value)) {
                errors.push(field.validation.message || `${field.label} is invalid`);
            }
        }

        return errors;
    }, []);

    const handleInputChange = useCallback((event: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value, type } = event.target;
        const field = PROFILE_CONFIG.fields[name as keyof ProfileData];
        
        // Don't update if field is not editable
        if (field?.editable === false) return;
        
        // Clear field errors on change
        setFieldErrors(prev => ({ ...prev, [name]: [] }));
        
        // Handle numeric fields
        if (type === 'number') {
            const numValue = value === '' ? null : Number(value);
            setFormData(prev => ({
                ...prev,
                [name]: numValue
            }));
            return;
        }
        
        // Handle other fields
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
    }, []);

    const handleSubmit = useCallback(async (event: React.FormEvent) => {
        event.preventDefault();
        setSaveSuccess(false);
        setSaveError(null);
        setFieldErrors({});

        // Validate all fields
        const errors: Record<string, string[]> = {};
        let hasErrors = false;

        Object.entries(PROFILE_CONFIG.fields).forEach(([fieldName, field]) => {
            // Skip validation for non-editable fields
            if (field.editable === false) return;
            
            const value = formData[fieldName as keyof ProfileData];
            const fieldErrors = validateField(fieldName as keyof ProfileData, value);
            if (fieldErrors.length > 0) {
                errors[fieldName] = fieldErrors;
                hasErrors = true;
            }
        });

        if (hasErrors) {
            setFieldErrors(errors);
            setSaveError('Please correct the errors below');
            return;
        }

        try {
            // Ensure numeric fields are properly typed
            const processedData = {
                ...formData,
                age: formData.age ? Number(formData.age) : null,
                height: formData.height ? Number(formData.height) : null,
                weight: formData.weight ? Number(formData.weight) : null
            };
            
            await updateProfile(processedData);
            setSaveSuccess(true);
            Events.dispatch(PROFILE_EVENTS.PROFILE_UPDATED, { profile: processedData });
        } catch (error) {
            const profileError = error as ProfileError;
            setSaveError(profileError.message || 'Failed to save profile');
            Events.dispatch(PROFILE_EVENTS.PROFILE_UPDATE_FAILED, { error: profileError });
        }
    }, [formData, updateProfile, validateField]);

    if (isLoading) {
        return <div className="loading">Loading profile data...</div>;
    }

    // Group fields by section
    const coreFields = ['username', 'email', 'displayName'];
    const customFields = Object.keys(PROFILE_CONFIG.fields).filter(field => !coreFields.includes(field));

    return (
        <form onSubmit={handleSubmit} className="profile-form">
            <SaveAlert
                success={saveSuccess}
                error={saveError}
                onDismiss={() => {
                    setSaveSuccess(false);
                    setSaveError(null);
                }}
            />

            {/* WordPress Core Fields Section */}
            <div className="form-section">
                <h2>WordPress Account</h2>
                {coreFields.map(fieldName => {
                    const field = PROFILE_CONFIG.fields[fieldName as keyof ProfileData];
                    const errors = fieldErrors[fieldName] || [];
                    return (
                        <div key={fieldName} className="form-field">
                            <label htmlFor={fieldName}>{field.label}</label>
                            <input
                                type={field.type}
                                id={fieldName}
                                name={fieldName}
                                value={formData[fieldName as keyof ProfileData] || ''}
                                onChange={handleInputChange}
                                required={field.required}
                                disabled={field.editable === false}
                                className={errors.length > 0 ? 'has-error' : ''}
                            />
                            {errors.map((error, index) => (
                                <div key={index} className="field-error">
                                    {error}
                                </div>
                            ))}
                        </div>
                    );
                })}
            </div>

            {/* Custom Profile Fields Section */}
            <div className="form-section">
                <h2>Profile Information</h2>
                {customFields.map(fieldName => {
                    const field = PROFILE_CONFIG.fields[fieldName as keyof ProfileData];
                    const errors = fieldErrors[fieldName] || [];
                    return (
                        <div key={fieldName} className="form-field">
                            <label htmlFor={fieldName}>{field.label}</label>
                            {field.type === 'select' ? (
                                <select
                                    id={fieldName}
                                    name={fieldName}
                                    value={formData[fieldName as keyof ProfileData] || ''}
                                    onChange={handleInputChange}
                                    required={field.required}
                                    className={errors.length > 0 ? 'has-error' : ''}
                                >
                                    {field.options?.map(option => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                            ) : (
                                <input
                                    type={field.type}
                                    id={fieldName}
                                    name={fieldName}
                                    value={formData[fieldName as keyof ProfileData] || ''}
                                    onChange={handleInputChange}
                                    required={field.required}
                                    min={field.validation?.min}
                                    max={field.validation?.max}
                                    className={errors.length > 0 ? 'has-error' : ''}
                                />
                            )}
                            {errors.map((error, index) => (
                                <div key={index} className="field-error">
                                    {error}
                                </div>
                            ))}
                        </div>
                    );
                })}
            </div>

            <div className="form-actions">
                <button type="submit" disabled={isLoading}>
                    {isLoading ? 'Saving...' : 'Save Profile'}
                </button>
            </div>

            <style jsx>{`
                .profile-form {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }

                .form-section {
                    margin-bottom: 40px;
                    padding-bottom: 20px;
                    border-bottom: 1px solid #eee;
                }

                .form-section h2 {
                    margin-bottom: 20px;
                    color: #333;
                    font-size: 1.5rem;
                }

                .form-field {
                    margin-bottom: 20px;
                }

                label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: 500;
                }

                input,
                select {
                    width: 100%;
                    padding: 8px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    font-size: 16px;
                }

                input:disabled {
                    background-color: #f5f5f5;
                    cursor: not-allowed;
                }

                input.has-error,
                select.has-error {
                    border-color: #dc3545;
                }

                .field-error {
                    color: #dc3545;
                    font-size: 14px;
                    margin-top: 5px;
                }

                .form-actions {
                    margin-top: 30px;
                    text-align: right;
                }

                button {
                    background-color: #007bff;
                    color: white;
                    padding: 10px 20px;
                    border: none;
                    border-radius: 4px;
                    font-size: 16px;
                    cursor: pointer;
                }

                button:disabled {
                    background-color: #ccc;
                    cursor: not-allowed;
                }

                .loading {
                    text-align: center;
                    padding: 20px;
                    font-size: 18px;
                    color: #666;
                }
            `}</style>
        </form>
    );
} 
} 