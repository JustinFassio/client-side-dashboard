import { ProfileData } from '../types/profile';

export const validateProfileField = (field: keyof ProfileData, value: any): string | null => {
    if (value === null || value === undefined || value === '') {
        const requiredFields = ['age', 'gender', 'height', 'weight', 'fitnessLevel', 'activityLevel'];
        return requiredFields.includes(field) ? 'Field cannot be empty' : null;
    }

    switch (field) {
        case 'email':
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) 
                ? null 
                : 'Invalid email address';

        case 'age':
            const age = Number(value);
            return age >= 13 && age <= 120 
                ? null 
                : 'Age must be between 13 and 120';

        case 'height':
            const height = Number(value);
            return height >= 100 && height <= 250 
                ? null 
                : 'Height must be between 100cm and 250cm';

        case 'weight':
            const weight = Number(value);
            return weight >= 30 && weight <= 300 
                ? null 
                : 'Weight must be between 30kg and 300kg';

        case 'fitnessLevel':
            const validFitnessLevels = ['beginner', 'intermediate', 'advanced'];
            return validFitnessLevels.includes(value) 
                ? null 
                : 'Invalid fitness level';

        case 'activityLevel':
            const validActivityLevels = [
                'sedentary',
                'lightly_active',
                'moderately_active',
                'very_active',
                'extra_active'
            ];
            return validActivityLevels.includes(value) 
                ? null 
                : 'Invalid activity level';

        case 'gender':
            const validGenders = ['male', 'female', 'other', 'prefer_not_to_say'];
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