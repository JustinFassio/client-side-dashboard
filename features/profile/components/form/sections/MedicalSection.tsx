import React from 'react';
import FormField from '../fields/FormField';
import { ProfileData } from '../../../types/profile';
import { FormValidationResult } from '../../../types/validation';

interface MedicalSectionProps {
    data: Partial<ProfileData>;
    onChange: (name: string, value: any) => void;
    validation?: FormValidationResult;
}

export const MedicalSection: React.FC<MedicalSectionProps> = ({
    data,
    onChange,
    validation
}) => {
    return (
        <div className="form-section">
            <h2>Medical Information</h2>
            
            <FormField
                name="medicalConditions"
                label="Medical Conditions"
                type="select"
                value={data.medicalConditions}
                onChange={onChange}
                validation={validation?.fieldErrors?.medicalConditions && {
                    isValid: false,
                    errors: validation.fieldErrors.medicalConditions
                }}
                options={[
                    { value: 'none', label: 'None' },
                    { value: 'heart_condition', label: 'Heart Condition' },
                    { value: 'asthma', label: 'Asthma' },
                    { value: 'diabetes', label: 'Diabetes' },
                    { value: 'hypertension', label: 'Hypertension' },
                    { value: 'other', label: 'Other' }
                ]}
                required
            />
            
            <FormField
                name="exerciseLimitations"
                label="Exercise Limitations"
                type="select"
                value={data.exerciseLimitations}
                onChange={onChange}
                validation={validation?.fieldErrors?.exerciseLimitations && {
                    isValid: false,
                    errors: validation.fieldErrors.exerciseLimitations
                }}
                options={[
                    { value: 'none', label: 'None' },
                    { value: 'joint_pain', label: 'Joint Pain' },
                    { value: 'back_pain', label: 'Back Pain' },
                    { value: 'limited_mobility', label: 'Limited Mobility' },
                    { value: 'balance_issues', label: 'Balance Issues' },
                    { value: 'other', label: 'Other' }
                ]}
            />
            
            <FormField
                name="medications"
                label="Current Medications"
                type="text"
                value={data.medications}
                onChange={onChange}
                validation={validation?.fieldErrors?.medications && {
                    isValid: false,
                    errors: validation.fieldErrors.medications
                }}
            />

            {/* Note: InjuryTracker component will be integrated here later */}
            
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

export default MedicalSection; 