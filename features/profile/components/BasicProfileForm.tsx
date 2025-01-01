import React, { useState, useCallback } from 'react';
import { useProfile } from '../context/ProfileContext';
import { ProfileData, PROFILE_CONFIG } from '../types/profile';
import { SaveAlert } from './SaveAlert';

export function BasicProfileForm() {
    const { profile, updateProfile, isLoading, error } = useProfile();
    const [formData, setFormData] = useState<Partial<ProfileData>>(profile || PROFILE_CONFIG.validation.getDefaultProfile());
    const [saveSuccess, setSaveSuccess] = useState(false);
    const [saveError, setSaveError] = useState<string | null>(null);

    const handleInputChange = useCallback((event: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value, type } = event.target;
        
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

        // Log the form data before submission
        console.group('Profile Form Submission');
        console.log('Form data:', formData);
        console.log('Age value:', formData.age, typeof formData.age);

        const validation = PROFILE_CONFIG.validation.validateProfile(formData);
        if (!validation.isValid) {
            const errorMessages = Object.values(validation.errors)
                .flat()
                .filter(Boolean)
                .join(', ');
            setSaveError(errorMessages);
            console.log('Validation failed:', errorMessages);
            console.groupEnd();
            return;
        }

        try {
            // Ensure age is a number before submission
            const processedData = {
                ...formData,
                age: formData.age ? Number(formData.age) : undefined
            };
            
            console.log('Processed data:', processedData);
            await updateProfile(processedData);
            setSaveSuccess(true);
        } catch (error) {
            setSaveError(error instanceof Error ? error.message : 'Failed to save profile');
            console.error('Save error:', error);
        }
        console.groupEnd();
    }, [formData, updateProfile]);

    if (isLoading) {
        return <div className="loading">Loading profile data...</div>;
    }

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

            {Object.entries(PROFILE_CONFIG.fields).map(([fieldName, field]) => {
                const fieldErrors = error?.details?.[fieldName] || [];
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
                            />
                        )}
                        {fieldErrors.map((error, index) => (
                            <div key={index} className="field-error">
                                {error}
                            </div>
                        ))}
                    </div>
                );
            })}

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