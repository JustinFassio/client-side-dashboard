/**
 * Physical metrics interface
 */
export interface PhysicalMetric {
    type: 'height' | 'weight' | 'bodyFat' | 'muscleMass';
    value: number;
    unit: string;
    date: string;
}

export interface PhysicalMetrics {
    height: PhysicalMetric;
    weight: PhysicalMetric;
    bodyFat?: PhysicalMetric;
    muscleMass?: PhysicalMetric;
}

/**
 * Core profile data interface
 */
export interface ProfileData {
    id: number;
    username: string;
    email: string;
    displayName: string;
    firstName: string;
    lastName: string;
    age: number;
    gender: string;
    height: number;
    weight: number;
    fitnessLevel: 'beginner' | 'intermediate' | 'advanced';
    activityLevel: 'sedentary' | 'lightly_active' | 'moderately_active' | 'very_active' | 'extremely_active';
    medicalConditions: string[];
    exerciseLimitations: string[];
    medications: string;
    physicalMetrics: PhysicalMetric[];
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
    name: string;
    label: string;
    type: string;
    required: boolean;
    editable?: boolean;
    options?: Array<{
        value: string;
        label: string;
    }>;
    validation?: {
        pattern?: RegExp;
        message?: string;
        min?: number;
        max?: number;
    };
}

export interface ProfileConfig {
    fields: Record<string, ProfileFieldConfig>;
    validation: {
        validateField: (field: keyof ProfileData, value: any) => string[];
        validateProfile: (data: Partial<ProfileData>) => {
            errors: Record<string, string[]>;
        };
    };
}

/**
 * Profile configuration
 */
export const PROFILE_CONFIG: ProfileConfig = {
    fields: {
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
            required: true
        },
        lastName: {
            name: 'lastName',
            label: 'Last Name',
            type: 'text',
            required: true
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
                { value: 'male', label: 'Male' },
                { value: 'female', label: 'Female' },
                { value: 'other', label: 'Other' },
                { value: 'prefer_not_to_say', label: 'Prefer not to say' }
            ]
        },
        fitnessLevel: {
            name: 'fitnessLevel',
            label: 'Fitness Level',
            type: 'select',
            required: true,
            options: [
                { value: 'beginner', label: 'Beginner' },
                { value: 'intermediate', label: 'Intermediate' },
                { value: 'advanced', label: 'Advanced' }
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
                { value: 'extra_active', label: 'Extra Active' }
            ]
        },
        medicalConditions: {
            name: 'medicalConditions',
            label: 'Medical Conditions',
            type: 'select',
            required: false,
            options: [
                { value: 'none', label: 'None' },
                { value: 'heart_condition', label: 'Heart Condition' },
                { value: 'asthma', label: 'Asthma' },
                { value: 'diabetes', label: 'Diabetes' },
                { value: 'arthritis', label: 'Arthritis' },
                { value: 'other', label: 'Other' }
            ]
        },
        exerciseLimitations: {
            name: 'exerciseLimitations',
            label: 'Exercise Limitations',
            type: 'select',
            required: false,
            options: [
                { value: 'none', label: 'None' },
                { value: 'joint_pain', label: 'Joint Pain' },
                { value: 'back_pain', label: 'Back Pain' },
                { value: 'limited_mobility', label: 'Limited Mobility' },
                { value: 'other', label: 'Other' }
            ]
        },
        medications: {
            name: 'medications',
            label: 'Medications',
            type: 'textarea',
            required: false
        },
        height: {
            name: 'height',
            label: 'Height (cm)',
            type: 'number',
            required: true,
            validation: {
                min: 100,
                max: 250,
                message: 'Height must be between 100cm and 250cm'
            }
        },
        weight: {
            name: 'weight',
            label: 'Weight (kg)',
            type: 'number',
            required: true,
            validation: {
                min: 30,
                max: 300,
                message: 'Weight must be between 30kg and 300kg'
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

            if (config.validation && value !== null && value !== undefined && value !== '') {
                const { validation } = config;
                if (validation.min !== undefined && value < validation.min) {
                    errors.push(validation.message || `${config.label} must be at least ${validation.min}`);
                }
                if (validation.max !== undefined && value > validation.max) {
                    errors.push(validation.message || `${config.label} must be at most ${validation.max}`);
                }
                if (validation.pattern && !validation.pattern.test(String(value))) {
                    errors.push(validation.message || `${config.label} is invalid`);
                }
            }

            return errors;
        },

        validateProfile: (data: Partial<ProfileData>) => {
            const errors: Record<string, string[]> = {};
            Object.keys(PROFILE_CONFIG.fields).forEach((field) => {
                errors[field] = PROFILE_CONFIG.validation.validateField(
                    field as keyof ProfileData,
                    data[field as keyof ProfileData]
                );
            });
            return { errors };
        }
    }
}; 