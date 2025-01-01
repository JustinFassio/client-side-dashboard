/**
 * Core profile data interface
 */
export interface ProfileData {
    // WordPress core fields
    username: string;
    email: string;
    displayName: string;
    firstName: string;
    lastName: string;

    // Basic profile fields
    age: number | null;
    gender: string;
    height: number | null;  // in centimeters
    weight: number | null;  // in kilograms

    // Physical metrics
    fitnessLevel: 'beginner' | 'intermediate' | 'advanced' | 'expert' | null;
    activityLevel: 'sedentary' | 'lightly_active' | 'moderately_active' | 'very_active' | 'extremely_active' | null;

    // Medical information
    medicalConditions: string[];
    exerciseLimitations: string[];
    medications: string;
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
    | 'AUTH_ERROR'
    | 'NETWORK_ERROR'
    | 'SERVER_ERROR';

export interface ProfileError {
    code: ProfileErrorCode;
    message: string;
    details?: Record<string, string[]>;
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
        },
        fitnessLevel: {
            name: 'fitnessLevel',
            label: 'Fitness Level',
            type: 'select',
            required: true,
            options: [
                { value: 'beginner', label: 'Beginner' },
                { value: 'intermediate', label: 'Intermediate' },
                { value: 'advanced', label: 'Advanced' },
                { value: 'expert', label: 'Expert' }
            ]
        },
        activityLevel: {
            name: 'activityLevel',
            label: 'Activity Level',
            type: 'select',
            required: true,
            options: [
                { value: 'sedentary', label: 'Sedentary' },
                { value: 'lightly_active', label: 'Lightly Active' },
                { value: 'moderately_active', label: 'Moderately Active' },
                { value: 'very_active', label: 'Very Active' },
                { value: 'extremely_active', label: 'Extremely Active' }
            ]
        },
        medicalConditions: {
            name: 'medicalConditions',
            label: 'Medical Conditions',
            type: 'select',
            options: [
                { value: 'none', label: 'None' },
                { value: 'heart_condition', label: 'Heart Condition' },
                { value: 'asthma', label: 'Asthma' },
                { value: 'diabetes', label: 'Diabetes' },
                { value: 'hypertension', label: 'Hypertension' },
                { value: 'other', label: 'Other' }
            ]
        },
        exerciseLimitations: {
            name: 'exerciseLimitations',
            label: 'Exercise Limitations',
            type: 'select',
            options: [
                { value: 'none', label: 'None' },
                { value: 'joint_pain', label: 'Joint Pain' },
                { value: 'back_pain', label: 'Back Pain' },
                { value: 'limited_mobility', label: 'Limited Mobility' },
                { value: 'balance_issues', label: 'Balance Issues' },
                { value: 'other', label: 'Other' }
            ]
        },
        medications: {
            name: 'medications',
            label: 'Current Medications',
            type: 'text'
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
                injuries: [],
                fitnessLevel: [],
                activityLevel: [],
                medicalConditions: [],
                exerciseLimitations: [],
                medications: []
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
            height: undefined,
            weight: undefined,
            dominantSide: undefined,
            medicalClearance: false,
            medicalNotes: '',
            emergencyContactName: '',
            emergencyContactPhone: '',
            injuries: [],
            fitnessLevel: null,
            activityLevel: null,
            medicalConditions: [],
            exerciseLimitations: [],
            medications: ''
        })
    }
} as const; 