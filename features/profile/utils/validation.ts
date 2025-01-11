import { ProfileData } from '../types/profile';

export const validateProfileField = (field: keyof ProfileData, value: any): string | null => {
    if (value === null || value === undefined || value === '') {
        const requiredFields = ['age', 'gender', 'height', 'weight', 'fitnessLevel', 'activityLevel'];
        return requiredFields.includes(field) ? 'Field cannot be empty' : null;
    }

    // Pre-declare variables used in switch cases
    const numValue = Number(value);
    const validFitnessLevels = ['beginner', 'intermediate', 'advanced'];
    const validActivityLevels = [
        'sedentary',
        'lightly_active',
        'moderately_active',
        'very_active',
        'extra_active'
    ];
    const validGenders = ['male', 'female', 'other', 'prefer_not_to_say'];

    switch (field) {
        case 'email':
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) 
                ? null 
                : 'Invalid email address';

        case 'age':
            return numValue >= 13 && numValue <= 120 
                ? null 
                : 'Age must be between 13 and 120';

        case 'height':
            return numValue >= 100 && numValue <= 250 
                ? null 
                : 'Height must be between 100cm and 250cm';

        case 'weight':
            return numValue >= 30 && numValue <= 300 
                ? null 
                : 'Weight must be between 30kg and 300kg';

        case 'fitnessLevel':
            return validFitnessLevels.includes(value) 
                ? null 
                : 'Invalid fitness level';

        case 'activityLevel':
            return validActivityLevels.includes(value) 
                ? null 
                : 'Invalid activity level';

        case 'gender':
            return validGenders.includes(value) 
                ? null 
                : 'Invalid gender selection';

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

        default:
            return null;
    }
}; 