import { 
    ValidationService as IValidationService,
    ValidationConfig,
    ValidationResult,
    FormValidationResult,
    ValidatorFn
} from '../types/validation';
import { ProfileData } from '../types/profile';

export class ValidationService implements IValidationService {
    constructor() {
        this.initializeValidators();
    }

    private initializeValidators(): void {
        // Register default validators for profile fields
        this.validators = {
            email: [this.requiredValidator, this.emailValidator],
            displayName: [this.requiredValidator, this.lengthValidator],
            age: [this.numberRangeValidator],
            height: [this.numberRangeValidator],
            weight: [this.numberRangeValidator],
            gender: [this.requiredValidator]
        };
    }

    public validateField<T>(value: T, config: ValidationConfig): ValidationResult {
        const errors: string[] = [];

        // Required validation
        if (config.required && this.isEmpty(value)) {
            errors.push('This field is required');
        }

        // Number range validation
        if (typeof value === 'number') {
            if (config.min !== undefined && value < config.min) {
                errors.push(`Value must be at least ${config.min}`);
            }
            if (config.max !== undefined && value > config.max) {
                errors.push(`Value must be no more than ${config.max}`);
            }
        }

        // Pattern validation
        if (typeof value === 'string' && config.pattern && !config.pattern.test(value)) {
            errors.push(config.pattern.toString());
        }

        // Custom validation rules
        if (config.custom) {
            for (const rule of config.custom) {
                if (!rule.validate(value)) {
                    const message = typeof rule.message === 'function' 
                        ? rule.message(value)
                        : rule.message;
                    errors.push(message);
                }
            }
        }

        return {
            isValid: errors.length === 0,
            errors
        };
    }

    public validateForm(data: Partial<ProfileData>): FormValidationResult {
        const fieldErrors: Record<string, string[]> = {};
        const generalErrors: string[] = [];
        let isValid = true;

        // Validate each field
        for (const [field, value] of Object.entries(data)) {
            const config = this.getValidationConfig(field as keyof ProfileData);
            
            const result = this.validateField(value, config);
            if (!result.isValid) {
                fieldErrors[field] = result.errors;
                isValid = false;
            }
        }

        // Add any cross-field validation errors
        const crossFieldErrors = this.validateCrossFieldRules(data);
        if (crossFieldErrors.length > 0) {
            generalErrors.push(...crossFieldErrors);
            isValid = false;
        }

        return {
            isValid,
            fieldErrors,
            generalErrors
        };
    }

    public getFieldValidators(field: keyof ProfileData): ValidatorFn[] {
        return this.validators[field] || [];
    }

    // Helper methods
    private isEmpty(value: any): boolean {
        if (value === null || value === undefined) return true;
        if (typeof value === 'string') return value.trim() === '';
        if (Array.isArray(value)) return value.length === 0;
        return false;
    }

    private getValidationConfig(field: keyof ProfileData): ValidationConfig {
        const baseConfig: ValidationConfig = {};

        switch (field) {
            case 'email':
                return {
                    ...baseConfig,
                    required: true,
                    pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/
                };
            case 'displayName':
                return {
                    ...baseConfig,
                    required: true,
                    custom: [{
                        validate: (value: string) => value.length >= 2 && value.length <= 50,
                        message: 'Display name must be between 2 and 50 characters'
                    }]
                };
            case 'age':
                return {
                    ...baseConfig,
                    min: 13,
                    max: 120
                };
            case 'height':
                return {
                    ...baseConfig,
                    min: 50,
                    max: 300, // cm
                    custom: [{
                        validate: (value: number) => Number.isFinite(value) && value >= 50 && value <= 300,
                        message: 'Height must be between 50 and 300 centimeters'
                    }]
                };
            case 'weight':
                return {
                    ...baseConfig,
                    min: 0,
                    max: 500 // kg
                };
            default:
                return baseConfig;
        }
    }

    // Validator functions
    private requiredValidator: ValidatorFn = (value, config) => ({
        isValid: !this.isEmpty(value),
        errors: this.isEmpty(value) ? ['This field is required'] : []
    });

    private emailValidator: ValidatorFn = (value, config) => ({
        isValid: typeof value === 'string' && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
        errors: typeof value === 'string' && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) 
            ? [] 
            : ['Please enter a valid email address']
    });

    private lengthValidator: ValidatorFn = (value, config) => {
        if (typeof value !== 'string') return { isValid: true, errors: [] };
        const min = config.min || 0;
        const max = config.max || Infinity;
        const isValid = value.length >= min && value.length <= max;
        return {
            isValid,
            errors: isValid ? [] : [`Length must be between ${min} and ${max} characters`]
        };
    };

    private numberRangeValidator: ValidatorFn = (value, config) => {
        if (typeof value !== 'number') return { isValid: true, errors: [] };
        const min = config.min ?? -Infinity;
        const max = config.max ?? Infinity;
        const isValid = value >= min && value <= max;
        return {
            isValid,
            errors: isValid ? [] : [`Value must be between ${min} and ${max}`]
        };
    };

    // Cross-field validation
    private validateCrossFieldRules(data: Partial<ProfileData>): string[] {
        const errors: string[] = [];

        // Example: Validate height/weight ratio if both are present
        if (data.height && data.weight) {
            const bmi = data.weight / Math.pow(data.height / 100, 2);
            if (bmi < 10 || bmi > 50) {
                errors.push('The height and weight combination appears to be invalid');
            }
        }

        return errors;
    }
} 