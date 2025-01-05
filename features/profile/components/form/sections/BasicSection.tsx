import React from 'react';
import FormField from '../fields/FormField';
import { ProfileData } from '../../../types/profile';
import { FormValidationResult } from '../../../types/validation';

interface BasicSectionProps {
    data: Partial<ProfileData>;
    onChange: (name: string, value: any) => void;
    validation?: FormValidationResult;
}

export const BasicSection: React.FC<BasicSectionProps> = ({
    data,
    onChange,
    validation
}) => {
    return (
        <div className="form-section">
            <h2>Basic Information</h2>
            <p className="form-section__description">
                Update your personal details and preferences.
            </p>
            
            <FormField
                name="displayName"
                label="Display Name"
                type="text"
                value={data.displayName}
                onChange={onChange}
                validation={validation?.fieldErrors?.displayName && {
                    isValid: false,
                    errors: validation.fieldErrors.displayName
                }}
                required
            />
            
            <FormField
                name="age"
                label="Age"
                type="number"
                value={data.age}
                onChange={onChange}
                validation={validation?.fieldErrors?.age && {
                    isValid: false,
                    errors: validation.fieldErrors.age
                }}
                min={13}
                max={120}
            />
            
            <FormField
                name="gender"
                label="Gender"
                type="select"
                value={data.gender}
                onChange={onChange}
                validation={validation?.fieldErrors?.gender && {
                    isValid: false,
                    errors: validation.fieldErrors.gender
                }}
                options={[
                    { value: 'male', label: 'Male' },
                    { value: 'female', label: 'Female' },
                    { value: 'other', label: 'Other' },
                    { value: 'prefer_not_to_say', label: 'Prefer not to say' }
                ]}
            />
        </div>
    );
};

export default BasicSection; 