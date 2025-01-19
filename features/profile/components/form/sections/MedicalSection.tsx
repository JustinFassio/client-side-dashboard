import React from 'react';
import FormField from '../fields/FormField';
import { ProfileData } from '../../../types/profile';
import { FormValidationResult } from '../../../types/validation';
import { Button } from '../../../../../dashboard/components/Button';
import * as styles from './MedicalSection.module.css';

interface MedicalSectionProps {
    data: Partial<ProfileData>;
    onChange: (name: string, value: any) => void;
    validation?: FormValidationResult;
    onSave: () => Promise<void>;
    isSaving?: boolean;
    error?: string;
}

export const MedicalSection: React.FC<MedicalSectionProps> = ({
    data,
    onChange,
    validation,
    onSave,
    isSaving,
    error
}) => {
    return (
        <div className={styles.section}>
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
                isArray={true}
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
                isArray={true}
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

            {error && (
                <div className={styles.error} role="alert">
                    <p>{error}</p>
                </div>
            )}

            <div className={styles.actions}>
                <Button
                    variant="primary"
                    feature="profile"
                    onClick={onSave}
                    disabled={isSaving}
                    aria-busy={isSaving}
                >
                    {isSaving ? 'Saving...' : 'Save Medical Information'}
                </Button>
            </div>
        </div>
    );
};

export default MedicalSection; 