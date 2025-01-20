import { Exercise, ExerciseConstraints } from '../types/workout-types';

/**
 * Manages exercise constraints and validation for workout generation.
 * Ensures exercises are safe and appropriate based on user injuries, equipment availability,
 * experience level, and intensity requirements.
 */
export class ConstraintManager {
    /** Maps injury types to affected muscle groups for constraint validation */
    private readonly injuryMuscleMap: Record<string, string[]> = {
        knee: ['quadriceps', 'hamstrings', 'calves'],
        shoulder: ['deltoids', 'rotator cuff', 'upper back'],
        lower_back: ['lower back', 'erector spinae'],
        wrist: ['forearms', 'grip muscles'],
        ankle: ['calves', 'tibialis anterior']
    };

    /** Maps user experience levels to allowed exercise difficulty levels */
    private readonly difficultyLevels: Record<string, string[]> = {
        beginner: ['beginner'],
        intermediate: ['beginner', 'intermediate'],
        advanced: ['beginner', 'intermediate', 'advanced']
    };

    /**
     * Validates an exercise against a set of constraints.
     * @param exercise - The exercise to validate
     * @param constraints - The constraints to validate against
     * @returns boolean indicating if the exercise is valid for the given constraints
     */
    public validateExerciseForConstraints(
        exercise: Exercise,
        constraints: ExerciseConstraints
    ): boolean {
        // Check equipment constraints
        if (!this.validateExerciseForEquipment(exercise, constraints.equipment)) {
            return false;
        }

        // Check injury constraints
        if (!this.validateExerciseForInjuries(exercise, constraints.injuries)) {
            return false;
        }

        // Check experience level constraints
        if (!this.validateExerciseForExperience(exercise, constraints.experienceLevel)) {
            return false;
        }

        // Check intensity constraints if specified
        if (constraints.maxIntensity && !this.validateExerciseForIntensity(exercise, constraints.maxIntensity)) {
            return false;
        }

        return true;
    }

    /**
     * Validates if an exercise can be performed with available equipment.
     * @param exercise - The exercise to validate
     * @param availableEquipment - List of available equipment
     * @returns boolean indicating if the exercise can be performed with available equipment
     */
    private validateExerciseForEquipment(exercise: Exercise, availableEquipment: string[]): boolean {
        if (!availableEquipment || availableEquipment.length === 0) {
            return true; // No equipment constraints
        }

        return exercise.equipment.some(eq => availableEquipment.includes(eq));
    }

    /**
     * Validates if an exercise is safe to perform given a list of injuries.
     * @param exercise - The exercise to validate
     * @param injuries - List of user injuries
     * @returns boolean indicating if the exercise is safe for the given injuries
     */
    private validateExerciseForInjuries(exercise: Exercise, injuries: string[]): boolean {
        if (!injuries || injuries.length === 0) {
            return true; // No injury constraints
        }

        const affectedMuscles = this.getAffectedMuscles(injuries);
        return !exercise.targetMuscles.some(muscle => affectedMuscles.includes(muscle));
    }

    /**
     * Validates if an exercise matches the user's experience level.
     * @param exercise - The exercise to validate
     * @param experienceLevel - User's experience level
     * @returns boolean indicating if the exercise is appropriate for the experience level
     */
    private validateExerciseForExperience(
        exercise: Exercise,
        experienceLevel: 'beginner' | 'intermediate' | 'advanced'
    ): boolean {
        return this.difficultyLevels[experienceLevel as keyof typeof this.difficultyLevels]?.includes(exercise.difficulty) ?? false;
    }

    /**
     * Validates if an exercise's intensity is within the maximum allowed intensity.
     * @param exercise - The exercise to validate
     * @param maxIntensity - Maximum allowed intensity level
     * @returns boolean indicating if the exercise's intensity is within limits
     */
    private validateExerciseForIntensity(exercise: Exercise, maxIntensity: 'low' | 'medium' | 'high'): boolean {
        const intensityLevels = {
            low: ['beginner'],
            medium: ['beginner', 'intermediate'],
            high: ['beginner', 'intermediate', 'advanced']
        };

        return intensityLevels[maxIntensity].includes(exercise.difficulty);
    }

    /**
     * Gets a list of muscle groups affected by specified injuries.
     * @param injuries - List of user injuries
     * @returns Array of muscle groups affected by the injuries
     */
    private getAffectedMuscles(injuries: string[]): string[] {
        return injuries.flatMap(injury => 
            this.injuryMuscleMap[injury as keyof typeof this.injuryMuscleMap] || []
        );
    }

    /**
     * Suggests alternative exercises that are safe and appropriate given the constraints.
     * Returns up to 3 exercises that target similar muscle groups while respecting all constraints.
     * @param exercise - The original exercise to find alternatives for
     * @param constraints - Constraints that alternative exercises must satisfy
     * @param availableExercises - Pool of exercises to search for alternatives
     * @returns Array of up to 3 alternative exercises, sorted by similarity to the original
     */
    public suggestAlternativeExercises(
        exercise: Exercise,
        constraints: ExerciseConstraints,
        availableExercises: Exercise[]
    ): Exercise[] {
        // Filter exercises that match constraints and target similar muscles
        const validAlternatives = availableExercises.filter(alt => {
            // Skip if it's the same exercise
            if (alt.name === exercise.name) {
                return false;
            }

            // Must be valid for all constraints
            if (!this.validateExerciseForConstraints(alt, constraints)) {
                return false;
            }

            // Must target at least one similar muscle group
            return alt.targetMuscles.some(muscle => exercise.targetMuscles.includes(muscle));
        });

        // Sort by similarity to original exercise
        return validAlternatives.sort((a, b) => 
            this.calculateExerciseSimilarity(b, exercise) - 
            this.calculateExerciseSimilarity(a, exercise)
        ).slice(0, 3); // Return top 3 matches
    }

    /**
     * Calculates a similarity score between two exercises based on target muscles,
     * exercise type, and difficulty level.
     * @param a - First exercise to compare
     * @param b - Second exercise to compare
     * @returns Numerical similarity score (higher means more similar)
     */
    private calculateExerciseSimilarity(a: Exercise, b: Exercise): number {
        let similarity = 0;

        // Compare target muscles (highest weight)
        const sharedMuscles = a.targetMuscles.filter(muscle => 
            b.targetMuscles.includes(muscle)
        ).length;
        similarity += sharedMuscles * 2;

        // Compare exercise type
        if (a.type === b.type) {
            similarity += 1;
        }

        // Compare difficulty
        if (a.difficulty === b.difficulty) {
            similarity += 1;
        }

        return similarity;
    }
} 