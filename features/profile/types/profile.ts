/**
 * Core profile data interface aligned with WordPress backend
 */
export interface ProfileData {
    // Core WordPress user fields
    id: number;
    username: string;           // wp_users.user_login
    email: string | null;       // wp_users.user_email
    displayName: string;        // wp_users.display_name
    firstName: string;          // wp_usermeta.first_name
    lastName: string;          // wp_usermeta.last_name
    nickname: string;          // wp_usermeta.nickname
    roles: string[];           // wp_usermeta.wp_capabilities

    // Custom profile fields
    phone: string;
    age: number;
    dateOfBirth: string;
    gender: 'male' | 'female' | 'other' | '';
    dominantSide: 'left' | 'right' | '';
    medicalClearance: boolean;
    medicalNotes: string;
    emergencyContactName: string;
    emergencyContactPhone: string;
    injuries: Injury[];
}

export interface Injury {
    id: string;
    name: string;
    details: string;
    type: string;
    description: string;
    date: string;
    severity: string;
    status: string;
    isCustom?: boolean;
}

export interface FormValidationResult {
    isValid: boolean;
    generalErrors?: string[];
    fieldErrors?: {
        [key: string]: string[];
    };
}

/**
 * Profile state interface
 */
export interface ProfileState {
    isComplete: boolean;
    isLoading: boolean;
    error: ProfileError | null;
    data: ProfileData | null;
}

/**
 * Profile error types
 */
export type ProfileErrorCode = 
    | 'NETWORK_ERROR'
    | 'VALIDATION_ERROR'
    | 'NOT_FOUND'
    | 'INVALID_RESPONSE'
    | 'UNAUTHORIZED';

export interface ProfileError {
    code: ProfileErrorCode;
    message: string;
    details?: Record<string, string[]>;
    status?: number;
}

/**
 * Profile validation interface
 */
export interface ProfileValidation {
    isValid: boolean;
    errors: Record<keyof ProfileData, string[]>;
}

/**
 * Profile form field configuration
 */
export interface ProfileFieldValidation {
    pattern?: RegExp;
    message?: string;
    min?: number;
    max?: number;
}

export interface ProfileField {
    name: keyof ProfileData;
    label: string;
    type: 'text' | 'number' | 'select' | 'date' | 'tel' | 'checkbox' | 'textarea';
    required: boolean;
    editable?: boolean;
    validation?: ProfileFieldValidation;
    options?: Array<{
        value: string;
        label: string;
    }>;
}

export interface ProfileFieldConfig {
    name: keyof ProfileData;
    label: string;
    type: 'text' | 'number' | 'tel' | 'date' | 'select' | 'checkbox' | 'textarea';
    required: boolean;
    editable?: boolean;
    validation?: {
        pattern?: RegExp;
        message?: string;
        min?: number;
        max?: number;
    };
    options?: Array<{
        value: string;
        label: string;
    }>;
}

/**
 * Profile configuration
 */
export const PROFILE_CONFIG: Record<keyof ProfileData, ProfileFieldConfig> = {
    id: {
        name: 'id',
        label: 'ID',
        type: 'number',
        required: true,
        editable: false
    },
    username: {
        name: 'username',
        label: 'Username',
        type: 'text',
        required: true,
        editable: false,
        validation: {
            pattern: /^[a-zA-Z0-9_-]+$/,
            message: 'Username can only contain letters, numbers, underscores, and hyphens'
        }
    },
    email: {
        name: 'email',
        label: 'Email',
        type: 'text',
        required: true,
        validation: {
            pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            message: 'Please enter a valid email address'
        }
    },
    displayName: {
        name: 'displayName',
        label: 'Display Name',
        type: 'text',
        required: true,
        validation: {
            min: 2,
            max: 50,
            message: 'Display name must be between 2 and 50 characters'
        }
    },
    firstName: {
        name: 'firstName',
        label: 'First Name',
        type: 'text',
        required: true,
        validation: {
            min: 2,
            max: 50,
            message: 'First name must be between 2 and 50 characters'
        }
    },
    lastName: {
        name: 'lastName',
        label: 'Last Name',
        type: 'text',
        required: true,
        validation: {
            min: 2,
            max: 50,
            message: 'Last name must be between 2 and 50 characters'
        }
    },
    phone: {
        name: 'phone',
        label: 'Phone Number',
        type: 'tel',
        required: false,
        validation: {
            pattern: /^\+?[\d\s-()]+$/,
            message: 'Please enter a valid phone number'
        }
    },
    age: {
        name: 'age',
        label: 'Age',
        type: 'number',
        required: false,
        validation: {
            min: 13,
            max: 120,
            message: 'Age must be between 13 and 120'
        }
    },
    dateOfBirth: {
        name: 'dateOfBirth',
        label: 'Date of Birth',
        type: 'date',
        required: false
    },
    gender: {
        name: 'gender',
        label: 'Gender',
        type: 'select',
        required: false,
        options: [
            { value: '', label: 'Select Gender' },
            { value: 'male', label: 'Male' },
            { value: 'female', label: 'Female' },
            { value: 'other', label: 'Other' }
        ]
    },
    dominantSide: {
        name: 'dominantSide',
        label: 'Dominant Side',
        type: 'select',
        required: false,
        options: [
            { value: '', label: 'Select Dominant Side' },
            { value: 'left', label: 'Left' },
            { value: 'right', label: 'Right' }
        ]
    },
    medicalClearance: {
        name: 'medicalClearance',
        label: 'Medical Clearance',
        type: 'checkbox',
        required: false
    },
    medicalNotes: {
        name: 'medicalNotes',
        label: 'Medical Notes',
        type: 'textarea',
        required: false
    },
    emergencyContactName: {
        name: 'emergencyContactName',
        label: 'Emergency Contact Name',
        type: 'text',
        required: false
    },
    emergencyContactPhone: {
        name: 'emergencyContactPhone',
        label: 'Emergency Contact Phone',
        type: 'tel',
        required: false,
        validation: {
            pattern: /^\+?[\d\s-()]+$/,
            message: 'Please enter a valid phone number'
        }
    },
    injuries: {
        name: 'injuries',
        label: 'Injuries',
        type: 'text',
        required: false
    },
    nickname: {
        name: 'nickname',
        label: 'Nickname',
        type: 'text',
        required: false,
        validation: {
            min: 2,
            max: 50
        }
    },
    roles: {
        name: 'roles',
        label: 'Roles',
        type: 'text',
        required: true,
        editable: false
    }
}; 

/**
 * Interface representing a comparison between old and new endpoint responses
 * Used during development to verify field mappings and data consistency
 */
export interface EndpointComparison {
    /** Normalized profile data from the old endpoint */
    oldEndpoint: ProfileData | null;
    /** Normalized profile data from the new endpoint */
    newEndpoint: ProfileData | null;
    /** Array of field differences between the two endpoints */
    differences: {
        /** The field name that differs */
        field: string;
        /** The value from the old endpoint */
        oldValue: any;
        /** The value from the new endpoint */
        newValue: any;
    }[];
    /** Whether all core fields match between endpoints */
    fieldsMatch: boolean;
}

/**
 * Interface representing the result of an endpoint comparison operation
 * Includes both the comparison data and operation status
 */
export interface ComparisonResult {
    /** Whether the comparison operation completed successfully */
    success: boolean;
    /** The detailed comparison results if successful */
    comparison?: EndpointComparison;
    /** Error message if the comparison failed */
    error?: string;
} 