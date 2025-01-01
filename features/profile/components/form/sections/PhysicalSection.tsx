import React from 'react';
import FormField from '../fields/FormField';
import { ProfileData } from '../../../types/profile';
import { FormValidationResult } from '../../../types/validation';

interface PhysicalSectionProps {
    data: Partial<ProfileData>;
    onChange: (name: string, value: any) => void;
    validation?: FormValidationResult;
}

export const PhysicalSection: React.FC<PhysicalSectionProps> = ({
    data,
    onChange,
    validation
}) => {
    return (
        <div className="form-section">
            <h2>Physical Information</h2>
            
            <FormField
                name="height"
                label="Height (cm)"
                type="number"
                value={data.height}
                onChange={onChange}
                validation={validation?.fieldErrors?.height && {
                    isValid: false,
                    errors: validation.fieldErrors.height
                }}
                min={0}
                max={300}
            />
            
            <FormField
                name="weight"
                label="Weight (kg)"
                type="number"
                value={data.weight}
                onChange={onChange}
                validation={validation?.fieldErrors?.weight && {
                    isValid: false,
                    errors: validation.fieldErrors.weight
                }}
                min={0}
                max={500}
            />
            
            <FormField
                name="fitnessLevel"
                label="Fitness Level"
                type="select"
                value={data.fitnessLevel}
                onChange={onChange}
                validation={validation?.fieldErrors?.fitnessLevel && {
                    isValid: false,
                    errors: validation.fieldErrors.fitnessLevel
                }}
                options={[
                    { value: 'beginner', label: 'Beginner' },
                    { value: 'intermediate', label: 'Intermediate' },
                    { value: 'advanced', label: 'Advanced' },
                    { value: 'expert', label: 'Expert' }
                ]}
                required
            />
            
            <FormField
                name="activityLevel"
                label="Activity Level"
                type="select"
                value={data.activityLevel}
                onChange={onChange}
                validation={validation?.fieldErrors?.activityLevel && {
                    isValid: false,
                    errors: validation.fieldErrors.activityLevel
                }}
                options={[
                    { value: 'sedentary', label: 'Sedentary' },
                    { value: 'lightly_active', label: 'Lightly Active' },
                    { value: 'moderately_active', label: 'Moderately Active' },
                    { value: 'very_active', label: 'Very Active' },
                    { value: 'extremely_active', label: 'Extremely Active' }
                ]}
                required
            />

            {validation?.generalErrors && validation.generalErrors.length > 0 && (
                <div className="section-errors">
                    {validation.generalErrors.map((error, index) => (
                        <div key={index} className="error-message">
                            {error}
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
};

export default PhysicalSection; 