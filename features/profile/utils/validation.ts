import { ProfileData } from '../types/profile';

export function validateField(field: keyof ProfileData, value: any): string | null {
    if (value === undefined || value === null) {
        return null;
    }

    switch (field) {
        case 'heightCm':
            if (typeof value !== 'number' || value <= 0) {
                return 'Height must be a positive number';
            }
            break;

        case 'weightKg':
            if (typeof value !== 'number' || value <= 0) {
                return 'Weight must be a positive number';
            }
            break;

        case 'experienceLevel':
            if (!value || !['beginner', 'intermediate', 'advanced'].includes(value)) {
                return 'Please select a valid experience level';
            }
            break;

        case 'gender':
            if (value && !['male', 'female', 'other', ''].includes(value)) {
                return 'Please select a valid gender';
            }
            break;

        case 'age':
            if (typeof value !== 'number' || value < 0) {
                return 'Age must be a positive number';
            }
            break;

        case 'phone':
            if (typeof value !== 'string') {
                return 'Phone must be a text value';
            }
            break;

        case 'email':
            if (typeof value !== 'string' || !value.includes('@')) {
                return 'Please enter a valid email address';
            }
            break;

        case 'injuries':
            if (!Array.isArray(value)) {
                return 'Injuries must be a list';
            }
            break;

        default:
            return null;
    }

    return null;
}

export function getFieldError(field: keyof ProfileData, value: any): string | null {
    if (validateField(field, value)) {
        return null;
    }

    switch (field) {
        case 'heightCm':
            return 'Height must be between 100cm and 250cm';
        
        case 'weightKg':
            return 'Weight must be between 30kg and 200kg';
        
        case 'experienceLevel':
            return 'Experience level must be beginner, intermediate, or advanced';
        
        case 'age':
            return 'Age must be between 13 and 120';
        
        default:
            return 'Invalid value';
    }
}

export const validateProfileField = (field: keyof ProfileData, value: any): string | null => {
    if (value === null || value === undefined || value === '') {
        const requiredFields = ['age', 'gender', 'heightCm', 'weightKg', 'experienceLevel'];
        return requiredFields.includes(field) ? 'Field cannot be empty' : null;
    }

    // Pre-declare variables used in switch cases
    const numValue = Number(value);
    const validGenders = ['male', 'female', 'other', 'prefer_not_to_say'];

    switch (field) {
        case 'email':
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) 
                ? null 
                : 'Invalid email address';

        case 'gender':
            if (value && !['male', 'female', 'other', ''].includes(value)) {
                return 'Please select a valid gender';
            }
            break;

        case 'injuries':
            if (!Array.isArray(value)) {
                return 'Injuries must be an array';
            }
            return value.every(injury => 
                injury.id && 
                injury.name && 
                injury.status && 
                ['active', 'recovered'].includes(injury.status)
            ) ? null : 'Invalid injury data';

        case 'medicalConditions':
        case 'exerciseLimitations':
            if (!Array.isArray(value)) {
                return 'Must be an array of conditions';
            }
            return value.length === 0 || value.every(condition => typeof condition === 'string')
                ? null
                : 'Invalid condition format';

        case 'medications':
            return typeof value === 'string' ? null : 'Must be a text value';

        case 'experienceLevel':
            if (!value || !['beginner', 'intermediate', 'advanced'].includes(value)) {
                return 'Please select a valid experience level';
            }
            break;

        default:
            return null;
    }

    return null;
}; 