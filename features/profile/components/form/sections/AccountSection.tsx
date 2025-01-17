import React from 'react';
import { __ } from '@wordpress/i18n';
import { Section } from '../../../components/Section';
import { FormField } from '../fields/FormField';
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
        <Section title={__('Account Information')}>
            <div className="form-section__grid">
                <FormField
                    name="username"
                    label={__('Username')}
                    type="text"
                    value={data?.username || ''}
                    onChange={onChange}
                    disabled
                />
                <FormField
                    name="email"
                    label={__('Email')}
                    type="email"
                    value={data?.email || ''}
                    onChange={onChange}
                    disabled
                />
                <FormField
                    name="displayName"
                    label={__('Display Name')}
                    type="text"
                    value={data?.displayName || ''}
                    onChange={onChange}
                    disabled
                />
                <FormField
                    name="firstName"
                    label={__('First Name')}
                    type="text"
                    value={data?.firstName || ''}
                    onChange={onChange}
                    disabled
                />
                <FormField
                    name="lastName"
                    label={__('Last Name')}
                    type="text"
                    value={data?.lastName || ''}
                    onChange={onChange}
                    disabled
                />
            </div>
        </Section>
    );
}; 