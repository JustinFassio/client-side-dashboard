import React from 'react';
import FormField from '../fields/FormField';
import { ProfileData } from '../../../types/profile';
import { FormValidationResult } from '../../../types/validation';

interface AccountSectionProps {
    data: Partial<ProfileData>;
    onChange: (name: string, value: any) => void;
    validation?: FormValidationResult;
}

export const AccountSection: React.FC<AccountSectionProps> = ({
    data,
    onChange,
    validation
}) => {
    return (
        <div className="form-section">
            <h2>Account Information</h2>
            <p className="form-section__description">
                Update your account information below.
            </p>
            
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
                name="username"
                label="Username"
                type="text"
                value={data.username}
                onChange={onChange}
                validation={validation?.fieldErrors?.username && {
                    isValid: false,
                    errors: validation.fieldErrors.username
                }}
                required
                disabled
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
            
            <FormField
                name="phone"
                label="Phone Number"
                type="tel"
                value={data.phone}
                onChange={onChange}
                validation={validation?.fieldErrors?.phone && {
                    isValid: false,
                    errors: validation.fieldErrors.phone
                }}
            />
        </div>
    );
};

export default AccountSection; 