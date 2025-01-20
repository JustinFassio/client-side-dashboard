import { 
    WorkoutPlan, 
    Exercise, 
    UserProfile, 
    ExerciseConstraints,
    WorkoutError,
    WorkoutErrorCode
} from '../types/workout-types';

export interface WorkoutValidation {
    isValid: boolean;
    errors: string[];
    warnings: string[];
}

export class WorkoutValidator {
    private validateExercise(exercise: Exercise): string[] {
        const errors: string[] = [];
        
        if (!exercise.name) {
            errors.push('Exercise name is required');
        }
        
        if (!exercise.difficulty || !['beginner', 'intermediate', 'advanced'].includes(exercise.difficulty)) {
            errors.push('Invalid exercise difficulty level');
        }
        
        if (!exercise.equipment || exercise.equipment.length === 0) {
            errors.push('Exercise equipment is required');
        }
        
        if (!exercise.targetMuscles || exercise.targetMuscles.length === 0) {
            errors.push('Target muscles are required');
        }
        
        if (!exercise.type) {
            errors.push('Exercise type is required');
        }
        
        return errors;
    }

    public validateWorkoutPlan(plan: WorkoutPlan): WorkoutValidation {
        const validation: WorkoutValidation = {
            isValid: true,
            errors: [],
            warnings: []
        };

        // Validate required fields
        if (!plan.id) {
            validation.errors.push('Workout plan ID is required');
        }

        if (!plan.name) {
            validation.errors.push('Workout plan name is required');
        }

        if (!plan.exercises || plan.exercises.length === 0) {
            validation.errors.push('Workout plan must include at least one exercise');
        } else {
            // Validate each exercise
            plan.exercises.forEach((exercise, index) => {
                const exerciseErrors = this.validateExercise(exercise);
                if (exerciseErrors.length > 0) {
                    validation.errors.push(`Exercise ${index + 1} (${exercise.name || 'unnamed'}): ${exerciseErrors.join(', ')}`);
                }
            });
        }

        if (!plan.duration || plan.duration <= 0) {
            validation.errors.push('Invalid workout duration');
        }

        if (!plan.difficulty || !['beginner', 'intermediate', 'advanced'].includes(plan.difficulty)) {
            validation.errors.push('Invalid workout difficulty level');
        }

        // Add warnings for potential issues
        if (plan.exercises && plan.exercises.length > 10) {
            validation.warnings.push('Workout plan contains more than 10 exercises');
        }

        if (plan.duration && plan.duration > 120) {
            validation.warnings.push('Workout duration exceeds 2 hours');
        }

        validation.isValid = validation.errors.length === 0;
        return validation;
    }

    public validateExerciseSafety(exercise: Exercise, profile: UserProfile): boolean {
        // Check if exercise targets injured areas
        if (profile.injuries && profile.injuries.length > 0) {
            const injuryRelatedMuscles = new Set(profile.injuries.flatMap(injury => this.getAffectedMuscles(injury)));
            const exerciseMuscles = new Set(exercise.targetMuscles);
            
            if ([...injuryRelatedMuscles].some(muscle => exerciseMuscles.has(muscle))) {
                return false;
            }
        }

        // Check if exercise difficulty matches user's experience level
        const difficultyLevels = { beginner: 1, intermediate: 2, advanced: 3 };
        const exerciseDifficulty = difficultyLevels[exercise.difficulty];
        const userLevel = difficultyLevels[profile.experienceLevel];

        if (exerciseDifficulty > userLevel) {
            return false;
        }

        return true;
    }

    public validateConstraints(exercises: Exercise[], constraints: ExerciseConstraints): boolean {
        return exercises.every(exercise => {
            // Check equipment constraints
            if (constraints.equipment && constraints.equipment.length > 0) {
                const hasRequiredEquipment = exercise.equipment.some(eq => 
                    constraints.equipment.includes(eq)
                );
                if (!hasRequiredEquipment) return false;
            }

            // Check experience level constraints
            if (constraints.experienceLevel) {
                const difficultyLevels = { beginner: 1, intermediate: 2, advanced: 3 };
                const exerciseDifficulty = difficultyLevels[exercise.difficulty];
                const maxDifficulty = difficultyLevels[constraints.experienceLevel];
                if (exerciseDifficulty > maxDifficulty) return false;
            }

            // Check injury constraints
            if (constraints.injuries && constraints.injuries.length > 0) {
                const injuryRelatedMuscles = new Set(
                    constraints.injuries.flatMap(injury => this.getAffectedMuscles(injury))
                );
                const exerciseMuscles = new Set(exercise.targetMuscles);
                
                if ([...injuryRelatedMuscles].some(muscle => exerciseMuscles.has(muscle))) {
                    return false;
                }
            }

            return true;
        });
    }

    private getAffectedMuscles(injury: string): string[] {
        // Map injuries to affected muscle groups
        const injuryMuscleMap: Record<string, string[]> = {
            'shoulder': ['shoulders', 'deltoids', 'rotator cuff'],
            'knee': ['quadriceps', 'hamstrings', 'calves'],
            'back': ['lower back', 'lats', 'trapezius'],
            'wrist': ['forearms'],
            'ankle': ['calves', 'tibialis anterior'],
            'hip': ['hip flexors', 'glutes', 'quadriceps']
        };

        return injuryMuscleMap[injury] || [];
    }
} 