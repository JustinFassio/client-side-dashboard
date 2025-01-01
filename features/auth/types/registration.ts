/**
 * Registration data interface
 */
export interface RegistrationData {
    username: string;
    email: string;
    password: string;
    firstName: string;
    lastName: string;
    agreeToTerms: boolean;
}

/**
 * Registration state interface
 */
export interface RegistrationState {
    isLoading: boolean;
    error: RegistrationError | null;
    success: boolean;
    step: RegistrationStep;
}

/**
 * Registration steps enum
 */
export enum RegistrationStep {
    INITIAL = 'initial',
    PERSONAL_INFO = 'personal_info',
    ACCOUNT_SETUP = 'account_setup',
    VERIFICATION = 'verification',
    COMPLETE = 'complete'
}

/**
 * Registration error types
 */
export type RegistrationErrorCode =
    | 'VALIDATION_ERROR'
    | 'USERNAME_EXISTS'
    | 'EMAIL_EXISTS'
    | 'INVALID_EMAIL'
    | 'WEAK_PASSWORD'
    | 'TERMS_NOT_ACCEPTED'
    | 'SERVER_ERROR';

export interface RegistrationError {
    code: RegistrationErrorCode;
    message: string;
    details?: Record<string, string[]>;
}

/**
 * Registration validation interface
 */
export interface RegistrationValidation {
    isValid: boolean;
    errors: Record<keyof RegistrationData, string[]>;
}

/**
 * Registration field configuration
 */
export interface RegistrationField {
    name: keyof RegistrationData;
    label: string;
    type: 'text' | 'email' | 'password' | 'checkbox';
    required: boolean;
    validation?: {
        pattern?: RegExp;
        message?: string;
        minLength?: number;
        maxLength?: number;
    };
}

/**
 * Registration configuration
 */
export const REGISTRATION_CONFIG = {
    fields: {
        username: {
            name: 'username',
            label: 'Username',
            type: 'text',
            required: true,
            validation: {
                pattern: /^[a-zA-Z0-9_-]{3,20}$/,
                message: 'Username must be between 3 and 20 characters and can only contain letters, numbers, underscores, and hyphens'
            }
        },
        email: {
            name: 'email',
            label: 'Email',
            type: 'email',
            required: true,
            validation: {
                pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                message: 'Please enter a valid email address'
            }
        },
        password: {
            name: 'password',
            label: 'Password',
            type: 'password',
            required: true,
            validation: {
                minLength: 8,
                pattern: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/,
                message: 'Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character'
            }
        },
        firstName: {
            name: 'firstName',
            label: 'First Name',
            type: 'text',
            required: true,
            validation: {
                pattern: /^[a-zA-Z\s-]{2,30}$/,
                message: 'First name must be between 2 and 30 characters and can only contain letters, spaces, and hyphens'
            }
        },
        lastName: {
            name: 'lastName',
            label: 'Last Name',
            type: 'text',
            required: true,
            validation: {
                pattern: /^[a-zA-Z\s-]{2,30}$/,
                message: 'Last name must be between 2 and 30 characters and can only contain letters, spaces, and hyphens'
            }
        },
        agreeToTerms: {
            name: 'agreeToTerms',
            label: 'I agree to the Terms and Conditions',
            type: 'checkbox',
            required: true
        }
    } as const,

    validation: {
        validateField: (field: keyof RegistrationData, value: any): string[] => {
            const errors: string[] = [];
            const config = REGISTRATION_CONFIG.fields[field];

            if (config.required && !value) {
                errors.push(`${config.label} is required`);
                return errors;
            }

            if (!value) return errors;

            const validation = config.validation;
            if (!validation) return errors;

            if (validation.minLength && value.length < validation.minLength) {
                errors.push(`${config.label} must be at least ${validation.minLength} characters`);
            }

            if (validation.maxLength && value.length > validation.maxLength) {
                errors.push(`${config.label} must be no more than ${validation.maxLength} characters`);
            }

            if (validation.pattern && !validation.pattern.test(value)) {
                errors.push(validation.message || `${config.label} is invalid`);
            }

            return errors;
        },

        validateRegistration: (data: Partial<RegistrationData>): RegistrationValidation => {
            const errors: Record<keyof RegistrationData, string[]> = {
                username: [],
                email: [],
                password: [],
                firstName: [],
                lastName: [],
                agreeToTerms: []
            };

            let isValid = true;

            Object.keys(REGISTRATION_CONFIG.fields).forEach((field) => {
                const fieldErrors = REGISTRATION_CONFIG.validation.validateField(
                    field as keyof RegistrationData,
                    data[field as keyof RegistrationData]
                );
                errors[field as keyof RegistrationData] = fieldErrors;
                if (fieldErrors.length > 0) isValid = false;
            });

            return { isValid, errors };
        }
    }
}; 