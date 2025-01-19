import { WorkoutPreferences, WorkoutModification, WorkoutInput } from '../types/workout-types';

export class SecurityValidator {
    validateInput(input: unknown): asserts input is WorkoutInput {
        if (!input || typeof input !== 'object') {
            throw new Error('Invalid input: must be an object');
        }

        if (this.isWorkoutPreferences(input)) {
            this.validateWorkoutPreferences(input);
        } else if (this.isWorkoutModification(input)) {
            this.validateWorkoutModification(input);
        } else {
            throw new Error('Invalid input: unknown input type');
        }
    }

    private isWorkoutPreferences(input: any): input is WorkoutPreferences {
        return 'maxExercises' in input || 'minRestPeriod' in input;
    }

    private isWorkoutModification(input: any): input is WorkoutModification {
        return 'workoutId' in input && 'changes' in input;
    }

    private validateWorkoutPreferences(preferences: WorkoutPreferences): void {
        // Validate maxExercises
        if (preferences.maxExercises !== undefined) {
            if (typeof preferences.maxExercises !== 'number' || 
                preferences.maxExercises <= 0 || 
                preferences.maxExercises > 50) {
                throw new Error('Invalid maxExercises: must be a number between 1 and 50');
            }
        }

        // Validate minRestPeriod
        if (preferences.minRestPeriod !== undefined) {
            if (typeof preferences.minRestPeriod !== 'number' || 
                preferences.minRestPeriod < 0 || 
                preferences.minRestPeriod > 600) {
                throw new Error('Invalid minRestPeriod: must be a number between 0 and 600 seconds');
            }
        }

        // Validate intensity
        if (preferences.intensity !== undefined) {
            if (typeof preferences.intensity !== 'number' || 
                preferences.intensity < 1 || 
                preferences.intensity > 10) {
                throw new Error('Invalid intensity: must be a number between 1 and 10');
            }
        }

        // Validate duration
        if (preferences.duration !== undefined) {
            if (typeof preferences.duration !== 'number' || 
                preferences.duration < 5 || 
                preferences.duration > 180) {
                throw new Error('Invalid duration: must be between 5 and 180 minutes');
            }
        }

        // Validate equipment
        if (preferences.equipment !== undefined) {
            if (!Array.isArray(preferences.equipment)) {
                throw new Error('Invalid equipment: must be an array');
            }
            preferences.equipment.forEach(item => {
                if (typeof item !== 'string' || item.length === 0) {
                    throw new Error('Invalid equipment item: must be a non-empty string');
                }
            });
        }

        // Validate focusAreas
        if (preferences.focusAreas !== undefined) {
            if (!Array.isArray(preferences.focusAreas)) {
                throw new Error('Invalid focusAreas: must be an array');
            }
            preferences.focusAreas.forEach(area => {
                if (typeof area !== 'string' || area.length === 0) {
                    throw new Error('Invalid focus area: must be a non-empty string');
                }
            });
        }

        // Validate goals
        if (preferences.goals !== undefined) {
            if (!Array.isArray(preferences.goals)) {
                throw new Error('Invalid goals: must be an array');
            }
            preferences.goals.forEach(goal => {
                if (typeof goal !== 'string' || goal.length === 0) {
                    throw new Error('Invalid goal: must be a non-empty string');
                }
            });
        }
    }

    private validateWorkoutModification(modification: WorkoutModification): void {
        // Validate workoutId
        if (typeof modification.workoutId !== 'string' || modification.workoutId.length === 0) {
            throw new Error('Invalid workoutId: must be a non-empty string');
        }

        // Validate changes
        if (!Array.isArray(modification.changes)) {
            throw new Error('Invalid changes: must be an array');
        }

        modification.changes.forEach((change, index) => {
            if (!change.type || typeof change.type !== 'string') {
                throw new Error(`Invalid change type at index ${index}: must be a non-empty string`);
            }

            if (!change.target || typeof change.target !== 'string') {
                throw new Error(`Invalid change target at index ${index}: must be a non-empty string`);
            }

            if (change.value === undefined) {
                throw new Error(`Missing change value at index ${index}`);
            }

            // Validate specific change types
            switch (change.type) {
                case 'replace':
                case 'modify':
                    if (typeof change.value !== 'object') {
                        throw new Error(`Invalid change value at index ${index}: must be an object`);
                    }
                    break;
                case 'remove':
                    // No value validation needed for remove
                    break;
                case 'add':
                    if (typeof change.value !== 'object') {
                        throw new Error(`Invalid change value at index ${index}: must be an object`);
                    }
                    break;
                default:
                    throw new Error(`Invalid change type at index ${index}: ${change.type}`);
            }
        });
    }

    // Additional validation methods for other input types can be added here
} 