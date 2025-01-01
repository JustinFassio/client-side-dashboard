/**
 * Core profile data interface
 */
export interface ProfileData {
    // WordPress core user fields
    username: string;
    email: string;
    displayName: string;
    
    // Custom profile fields
    userId: number;
    firstName: string;
    lastName: string;
    age: number | null;
    gender: 'male' | 'female' | 'other' | 'prefer_not_to_say';
    phone?: string;
    dateOfBirth?: string;
    height?: number;
    weight?: number;
    dominantSide?: 'left' | 'right';
    medicalClearance?: boolean;
    medicalNotes?: string;
    emergencyContactName?: string;
    emergencyContactPhone?: string;
    injuries?: Array<{
        id: string;
        name: string;
        details: string;
    }>;
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
    | 'VALIDATION_ERROR'
    | 'NETWORK_ERROR'
    | 'AUTH_ERROR'
    | 'SERVER_ERROR';

export interface ProfileError {
    code: ProfileErrorCode;
    message: string;
    details?: Record<string, any>;
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
export interface ProfileField {
    name: keyof ProfileData;
    label: string;
    type: 'text' | 'number' | 'select' | 'date' | 'tel' | 'checkbox' | 'textarea';
    required: boolean;
    validation?: {
        min?: number;
        max?: number;
        pattern?: RegExp;
        message?: string;
    };
    options?: Array<{
        value: string;
        label: string;
    }>;
}

/**
 * Profile configuration
 */
export const PROFILE_CONFIG = {
    fields: {
        // WordPress core user fields
        username: {
            name: 'username',
            label: 'Username',
            type: 'text',
            required: true,
            editable: false, // Username cannot be changed
            validation: {
                pattern: /^[a-zA-Z0-9_-]+$/,
                message: 'Username can only contain letters, numbers, underscores, and hyphens'
            }
        },
        email: {
            name: 'email',
            label: 'Email Address',
            type: 'email',
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
                pattern: /^[a-zA-Z0-9\s-']+$/,
                message: 'Display name can only contain letters, numbers, spaces, hyphens, and apostrophes'
            }
        },
        // Custom profile fields
        firstName: {
            name: 'firstName',
            label: 'First Name',
            type: 'text',
            required: true,
            validation: {
                pattern: /^[a-zA-Z\s-]+$/,
                message: 'First name can only contain letters, spaces, and hyphens'
            }
        },
        lastName: {
            name: 'lastName',
            label: 'Last Name',
            type: 'text',
            required: true,
            validation: {
                pattern: /^[a-zA-Z\s-]+$/,
                message: 'Last name can only contain letters, spaces, and hyphens'
            }
        },
        age: {
            name: 'age',
            label: 'Age',
            type: 'number',
            required: true,
            validation: {
                min: 13,
                max: 120,
                message: 'Age must be between 13 and 120'
            }
        },
        gender: {
            name: 'gender',
            label: 'Gender',
            type: 'select',
            required: true,
            options: [
                { value: 'prefer_not_to_say', label: 'Prefer not to say' },
                { value: 'male', label: 'Male' },
                { value: 'female', label: 'Female' },
                { value: 'other', label: 'Other' }
            ]
        },
        phone: {
            name: 'phone',
            label: 'Phone',
            type: 'tel',
            required: false,
            validation: {
                pattern: /^[0-9+\-\(\)\s]*$/,
                message: 'Invalid phone number format'
            }
        },
        dateOfBirth: {
            name: 'dateOfBirth',
            label: 'Date of Birth',
            type: 'date',
            required: false
        },
        height: {
            name: 'height',
            label: 'Height (cm)',
            type: 'number',
            required: false,
            validation: {
                min: 0,
                max: 300,
                message: 'Height must be between 0 and 300 cm'
            }
        },
        weight: {
            name: 'weight',
            label: 'Weight (kg)',
            type: 'number',
            required: false,
            validation: {
                min: 0,
                max: 500,
                message: 'Weight must be between 0 and 500 kg'
            }
        }
    } as const,
    
    validation: {
        validateField: (field: keyof ProfileData, value: any): string[] => {
            const errors: string[] = [];
            const config = PROFILE_CONFIG.fields[field];

            if (!config) return errors;

            if (config.required && (value === null || value === undefined || value === '')) {
                errors.push(`${config.label} is required`);
                return errors;
            }

            if (value === null || value === undefined || value === '') return errors;

            const validation = config.validation;
            if (!validation) return errors;

            if (validation.min !== undefined && value < validation.min) {
                errors.push(`${config.label} must be at least ${validation.min}`);
            }

            if (validation.max !== undefined && value > validation.max) {
                errors.push(`${config.label} must be no more than ${validation.max}`);
            }

            if (validation.pattern && !validation.pattern.test(value)) {
                errors.push(validation.message || `${config.label} is invalid`);
            }

            return errors;
        },

        validateProfile: (data: Partial<ProfileData>): ProfileValidation => {
            const errors: Record<keyof ProfileData, string[]> = {
                userId: [],
                firstName: [],
                lastName: [],
                age: [],
                gender: [],
                phone: [],
                dateOfBirth: [],
                height: [],
                weight: [],
                dominantSide: [],
                medicalClearance: [],
                medicalNotes: [],
                emergencyContactName: [],
                emergencyContactPhone: [],
                injuries: []
            };

            let isValid = true;

            Object.keys(PROFILE_CONFIG.fields).forEach((field) => {
                const fieldErrors = PROFILE_CONFIG.validation.validateField(
                    field as keyof ProfileData,
                    data[field as keyof ProfileData]
                );
                errors[field as keyof ProfileData] = fieldErrors;
                if (fieldErrors.length > 0) isValid = false;
            });

            return { isValid, errors };
        },

        getDefaultProfile: (): ProfileData => ({
            userId: 0,
            firstName: '',
            lastName: '',
            age: null,
            gender: 'prefer_not_to_say',
            phone: '',
            dateOfBirth: '',
            height: null,
            weight: null,
            dominantSide: undefined,
            medicalClearance: false,
            medicalNotes: '',
            emergencyContactName: '',
            emergencyContactPhone: '',
            injuries: []
        })
    }
} as const; 