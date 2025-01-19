import { WorkoutPlan, ValidationResult, Exercise } from '../types/workout-types';

interface ValidationRules {
    maxExercises: number;
    minRestPeriod: number;
    requiredWarmup: boolean;
}

interface ValidationError {
    code: string;
    message: string;
    details?: any;
}

export class WorkoutValidator {
    async validate(workout: WorkoutPlan, rules: ValidationRules): Promise<ValidationResult> {
        const errors: ValidationError[] = [];

        // Validate basic structure
        if (!workout || !workout.exercises || !Array.isArray(workout.exercises)) {
            errors.push({
                code: 'INVALID_STRUCTURE',
                message: 'Workout plan must contain an array of exercises'
            });
            return { isValid: false, errors };
        }

        // Check number of exercises
        if (workout.exercises.length > rules.maxExercises) {
            errors.push({
                code: 'TOO_MANY_EXERCISES',
                message: `Workout contains more than ${rules.maxExercises} exercises`,
                details: { count: workout.exercises.length, max: rules.maxExercises }
            });
        }

        // Validate rest periods
        const invalidRestPeriods = workout.exercises.filter(
            exercise => exercise.restPeriod && exercise.restPeriod < rules.minRestPeriod
        );
        if (invalidRestPeriods.length > 0) {
            errors.push({
                code: 'INSUFFICIENT_REST',
                message: `Some exercises have rest periods shorter than ${rules.minRestPeriod} seconds`,
                details: { exercises: invalidRestPeriods.map(e => e.id) }
            });
        }

        // Check for warmup if required
        if (rules.requiredWarmup) {
            const hasWarmup = workout.exercises.some(exercise => 
                exercise.type === 'warmup' || exercise.tags?.includes('warmup')
            );
            if (!hasWarmup) {
                errors.push({
                    code: 'MISSING_WARMUP',
                    message: 'Workout plan must include a warmup exercise'
                });
            }
        }

        // Validate individual exercises
        for (const exercise of workout.exercises) {
            const exerciseErrors = this.validateExercise(exercise);
            if (exerciseErrors.length > 0) {
                errors.push(...exerciseErrors);
            }
        }

        // Validate exercise sequence
        const sequenceErrors = this.validateExerciseSequence(workout.exercises);
        if (sequenceErrors.length > 0) {
            errors.push(...sequenceErrors);
        }

        return {
            isValid: errors.length === 0,
            errors: errors.length > 0 ? errors : undefined
        };
    }

    private validateExercise(exercise: Exercise): ValidationError[] {
        const errors: ValidationError[] = [];

        // Check required fields
        if (!exercise.id) {
            errors.push({
                code: 'MISSING_ID',
                message: 'Exercise must have an ID',
                details: { exercise }
            });
        }

        if (!exercise.name) {
            errors.push({
                code: 'MISSING_NAME',
                message: 'Exercise must have a name',
                details: { exerciseId: exercise.id }
            });
        }

        // Validate sets and reps
        if (typeof exercise.sets !== 'number' || exercise.sets <= 0) {
            errors.push({
                code: 'INVALID_SETS',
                message: 'Exercise must have a valid number of sets',
                details: { exerciseId: exercise.id, sets: exercise.sets }
            });
        }

        if (typeof exercise.reps !== 'number' || exercise.reps <= 0) {
            errors.push({
                code: 'INVALID_REPS',
                message: 'Exercise must have a valid number of reps',
                details: { exerciseId: exercise.id, reps: exercise.reps }
            });
        }

        // Validate intensity if present
        if (exercise.intensity !== undefined) {
            if (typeof exercise.intensity !== 'number' || 
                exercise.intensity < 0 || 
                exercise.intensity > 100) {
                errors.push({
                    code: 'INVALID_INTENSITY',
                    message: 'Exercise intensity must be between 0 and 100',
                    details: { exerciseId: exercise.id, intensity: exercise.intensity }
                });
            }
        }

        return errors;
    }

    private validateExerciseSequence(exercises: Exercise[]): ValidationError[] {
        const errors: ValidationError[] = [];

        // Check for proper exercise order (warmup → main → cooldown)
        const warmupExercises = exercises.filter(e => 
            e.type === 'warmup' || e.tags?.includes('warmup')
        );
        const cooldownExercises = exercises.filter(e => 
            e.type === 'cooldown' || e.tags?.includes('cooldown')
        );

        // All warmup exercises should come before non-warmup exercises
        const lastWarmupIndex = exercises.findIndex(e => 
            e.type === 'warmup' || e.tags?.includes('warmup')
        );
        const firstNonWarmupIndex = exercises.findIndex(e => 
            e.type !== 'warmup' && !e.tags?.includes('warmup')
        );

        if (lastWarmupIndex > firstNonWarmupIndex && firstNonWarmupIndex !== -1) {
            errors.push({
                code: 'INVALID_WARMUP_SEQUENCE',
                message: 'Warmup exercises must come before main exercises'
            });
        }

        // All cooldown exercises should come after non-cooldown exercises
        const firstCooldownIndex = exercises.findIndex(e => 
            e.type === 'cooldown' || e.tags?.includes('cooldown')
        );
        const lastNonCooldownIndex = exercises.findIndex(e => 
            e.type !== 'cooldown' && !e.tags?.includes('cooldown')
        );

        if (firstCooldownIndex < lastNonCooldownIndex && firstCooldownIndex !== -1) {
            errors.push({
                code: 'INVALID_COOLDOWN_SEQUENCE',
                message: 'Cooldown exercises must come after main exercises'
            });
        }

        return errors;
    }
} 