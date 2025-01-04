import React from 'react';
import { ProfileData } from '../../types/profile';

interface ProfileFormProps {
    profile: ProfileData;
    onSubmit: (data: ProfileData) => void;
    onCancel: () => void;
}

export const ProfileForm: React.FC<ProfileFormProps> = ({ profile, onSubmit, onCancel }) => {
    const [formData, setFormData] = React.useState<ProfileData>(profile);
    const [errors, setErrors] = React.useState<Record<string, string>>({});

    const handleChange = (field: keyof ProfileData, value: string | number | boolean) => {
        setFormData(prev => ({
            ...prev,
            [field]: value
        }));
    };

    const validateForm = (): boolean => {
        const newErrors: Record<string, string> = {};
        let isValid = true;

        // Add validation logic here
        Object.entries(formData).forEach(([key, value]) => {
            if (typeof value === 'string' && !value.trim()) {
                newErrors[key] = 'This field is required';
                isValid = false;
            }
        });

        setErrors(newErrors);
        return isValid;
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (validateForm()) {
            onSubmit(formData);
        }
    };

    return (
        <form onSubmit={handleSubmit} className="profile-form">
            {Object.entries(formData).map(([key, value]) => {
                const fieldKey = key as keyof ProfileData;
                return (
                    <div key={key} className="form-field">
                        <label htmlFor={key}>{key}</label>
                        <input
                            id={key}
                            type="text"
                            value={String(value)}
                            onChange={(e) => handleChange(fieldKey, e.target.value)}
                        />
                        {errors[key] && (
                            <span className="error">{errors[key]}</span>
                        )}
                    </div>
                );
            })}
            <div className="form-actions">
                <button type="submit">Save</button>
                <button type="button" onClick={onCancel}>Cancel</button>
            </div>
        </form>
    );
}; 