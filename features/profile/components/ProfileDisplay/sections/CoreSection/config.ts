import { ProfileField } from '../../../../types/profile-display';

export interface CoreFields {
    username: ProfileField<string>;
    firstName: ProfileField<string>;
    lastName: ProfileField<string>;
    email: ProfileField<string>;
    displayName: ProfileField<string>;
}

export const CORE_FIELD_CONFIG = {
    username: {
        label: 'Username',
        editable: false,
        validation: /^[a-zA-Z0-9_-]+$/
    },
    firstName: {
        label: 'First Name',
        editable: true,
        validation: /^[a-zA-Z\s\-']+$/
    },
    lastName: {
        label: 'Last Name',
        editable: true,
        validation: /^[a-zA-Z\s\-']+$/
    },
    email: {
        label: 'Email Address',
        editable: true,
        validation: /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    },
    displayName: {
        label: 'Display Name',
        editable: true,
        validation: /^[a-zA-Z0-9\s\-']+$/
    }
} as const;

export const getDefaultCoreFields = (): CoreFields => ({
    username: {
        value: '',
        lastUpdated: new Date().toISOString(),
        isEditing: false
    },
    firstName: {
        value: '',
        lastUpdated: new Date().toISOString(),
        isEditing: false,
        validation: CORE_FIELD_CONFIG.firstName.validation
    },
    lastName: {
        value: '',
        lastUpdated: new Date().toISOString(),
        isEditing: false,
        validation: CORE_FIELD_CONFIG.lastName.validation
    },
    email: {
        value: '',
        lastUpdated: new Date().toISOString(),
        isEditing: false,
        validation: CORE_FIELD_CONFIG.email.validation
    },
    displayName: {
        value: '',
        lastUpdated: new Date().toISOString(),
        isEditing: false,
        validation: CORE_FIELD_CONFIG.displayName.validation
    }
}); 