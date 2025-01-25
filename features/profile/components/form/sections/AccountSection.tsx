import React from 'react';
import FormField from '../fields/FormField';
import { ProfileData } from '../../../types/profile';
import { FormValidationResult } from '../../../types/validation';
import { Button } from '../../../../../dashboard/components/Button';
import * as styles from './AccountSection.module.css';

interface AccountSectionProps {
    data: Partial<ProfileData>;
    onChange: (name: string, value: any) => void;
    validation?: FormValidationResult;
    onSave: () => Promise<void>;
    isSaving?: boolean;
    error?: string;
}

export const AccountSection: React.FC<AccountSectionProps> = ({
    data,
    onChange,
    validation,
    onSave,
    isSaving,
    error
}) => {
    return (
        <div className={styles.section}>
            <h2>Account Settings</h2>
            
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
                name="nickname"
                label="Nickname"
                type="text"
                value={data.nickname}
                onChange={onChange}
                validation={validation?.fieldErrors?.nickname && {
                    isValid: false,
                    errors: validation.fieldErrors.nickname
                }}
            />

            {error && (
                <div className={styles.error} role="alert">
                    <p>{error}</p>
                </div>
            )}

            <div className={styles.actions}>
                <Button
                    onClick={onSave}
                    disabled={isSaving}
                    isLoading={isSaving}
                    feature="physical"
                >
                    Save Account Settings
                </Button>
            </div>
        </div>
    );
};

export default AccountSection; 