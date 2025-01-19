import React from 'react';
import FormField from '../fields/FormField';
import { ProfileData } from '../../../types/profile';
import { FormValidationResult } from '../../../types/validation';
import { Button } from '../../../../../dashboard/components/Button';
import * as styles from './BasicSection.module.css';

interface BasicSectionProps {
    data: Partial<ProfileData>;
    onChange: (name: string, value: any) => void;
    validation?: FormValidationResult;
    onSave: () => Promise<void>;
    isSaving?: boolean;
    error?: string;
}

export const BasicSection: React.FC<BasicSectionProps> = ({
    data,
    onChange,
    validation,
    onSave,
    isSaving,
    error
}) => {
    return (
        <div className={styles.section}>
            <h2>Basic Information</h2>
            
            <FormField
                name="firstName"
                label="First Name"
                type="text"
                value={data.firstName}
                onChange={onChange}
                validation={validation?.fieldErrors?.firstName && {
                    isValid: false,
                    errors: validation.fieldErrors.firstName
                }}
                required
            />
            
            <FormField
                name="lastName"
                label="Last Name"
                type="text"
                value={data.lastName}
                onChange={onChange}
                validation={validation?.fieldErrors?.lastName && {
                    isValid: false,
                    errors: validation.fieldErrors.lastName
                }}
                required
            />
            
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
                name="email"
                label="Email"
                type="email"
                value={data.email}
                onChange={onChange}
                validation={validation?.fieldErrors?.email && {
                    isValid: false,
                    errors: validation.fieldErrors.email
                }}
                required
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
                    {isSaving ? 'Saving...' : 'Save Basic Information'}
                </Button>
            </div>
        </div>
    );
};

export default BasicSection; 