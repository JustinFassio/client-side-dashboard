import { ProfileData } from './profile';

export interface ValidationRule<T = any> {
    validate: (value: T) => boolean;
    message: string | ((value: T) => string);
}

export interface ValidationConfig {
    required?: boolean;
    min?: number;
    max?: number;
    pattern?: RegExp;
    custom?: ValidationRule[];
}

export interface FieldValidation {
    field: keyof ProfileData;
    rules: ValidationConfig;
}

export interface ValidationResult {
    isValid: boolean;
    errors: string[];
}

export interface FormValidationResult {
    isValid: boolean;
    fieldErrors: Record<string, string[]>;
    generalErrors: string[];
}

export type ValidatorFn<T = any> = (value: T, config: ValidationConfig) => ValidationResult;

export interface ValidationService {
    validateField: <T>(value: T, config: ValidationConfig) => ValidationResult;
    validateForm: (data: Partial<ProfileData>) => FormValidationResult;
    getFieldValidators: (field: keyof ProfileData) => ValidatorFn[];
} 