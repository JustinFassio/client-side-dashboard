import { WorkoutPreferences, WorkoutModification } from '../types/workout-types';

export class SecurityValidator {
    validateInput(input: WorkoutPreferences | WorkoutModification): void {
        if (!input) {
            throw new Error('Invalid input: Input cannot be null or undefined');
        }

        // Sanitize and validate all string inputs
        Object.entries(input).forEach(([key, value]) => {
            if (typeof value === 'string') {
                this.validateString(key, value);
            } else if (Array.isArray(value)) {
                value.forEach(item => {
                    if (typeof item === 'string') {
                        this.validateString(key, item);
                    }
                });
            }
        });
    }

    private validateString(field: string, value: string): void {
        // Check for potential XSS or injection attempts
        const dangerous = /<script|javascript:|data:|vbscript:|file:|alert\(|onclick|onload/i;
        if (dangerous.test(value)) {
            throw new Error(`Invalid input: Potentially dangerous content in ${field}`);
        }

        // Check for reasonable string length
        if (value.length > 1000) {
            throw new Error(`Invalid input: ${field} exceeds maximum length`);
        }
    }
} 