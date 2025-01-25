import { useCallback } from 'react';
import { ProfileData, FormValidationResult } from '../types/profile';

export const useProfileValidation = () => {
    const validateProfile = useCallback((data: Partial<ProfileData>): FormValidationResult => {
        const errors: { [key: string]: string[] } = {};
        const generalErrors: string[] = [];

        // Height and weight validation with unit conversion
        if (data.heightCm !== undefined) {
            if (typeof data.heightCm !== 'number' || data.heightCm < 100 || data.heightCm > 250) {
                errors.heightCm = ['Height must be between 100cm and 250cm'];
            }
        }

        if (data.weightKg !== undefined) {
            if (typeof data.weightKg !== 'number' || data.weightKg < 30 || data.weightKg > 200) {
                errors.weightKg = ['Weight must be between 30kg and 200kg'];
            }
        }

        // Experience level validation
        if (data.experienceLevel) {
            const validLevels = ['beginner', 'intermediate', 'advanced'];
            if (!validLevels.includes(data.experienceLevel)) {
                errors.experienceLevel = ['Invalid experience level'];
            }
        }

        // Injuries validation
        if (data.injuries && Array.isArray(data.injuries)) {
            data.injuries.forEach((injury, index) => {
                if (!injury.type || !injury.severity || !injury.status) {
                    if (!errors.injuries) errors.injuries = [];
                    errors.injuries.push(`Injury ${index + 1} is missing required fields`);
                }
            });
        }

        // Equipment validation
        if (data.equipment && Array.isArray(data.equipment)) {
            const validEquipment = ['dumbbells', 'barbell', 'kettlebell', 'resistance_bands', 'pull_up_bar', 'bench'];
            data.equipment.forEach((item, index) => {
                if (!validEquipment.includes(item)) {
                    if (!errors.equipment) errors.equipment = [];
                    errors.equipment.push(`Invalid equipment type at position ${index + 1}`);
                }
            });
        }

        // Fitness goals validation
        if (data.fitnessGoals && Array.isArray(data.fitnessGoals)) {
            const validGoals = ['strength', 'muscle_gain', 'fat_loss', 'endurance', 'flexibility'];
            data.fitnessGoals.forEach((goal, index) => {
                if (!validGoals.includes(goal)) {
                    if (!errors.fitnessGoals) errors.fitnessGoals = [];
                    errors.fitnessGoals.push(`Invalid fitness goal at position ${index + 1}`);
                }
            });
        }

        // Check for any validation errors
        const hasErrors = Object.keys(errors).length > 0 || generalErrors.length > 0;

        return {
            isValid: !hasErrors,
            fieldErrors: Object.keys(errors).length > 0 ? errors : undefined,
            generalErrors: generalErrors.length > 0 ? generalErrors : undefined
        };
    }, []);

    return {
        validateProfile
    };
}; 