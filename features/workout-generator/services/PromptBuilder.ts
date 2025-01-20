import { 
    WorkoutPreferences, 
    Exercise,
    AIPrompt,
    UserProfile,
    TrainingPreferences,
    EquipmentSet
} from '../types/workout-types';

/**
 * Represents constraints for exercises based on user injuries
 */
export interface InjuryConstraint {
    injury: string;
    excludedExercises: string[];
    excludedMuscleGroups: string[];
    maxIntensity?: 'low' | 'medium' | 'high';
}

/**
 * Represents all constraints that should be applied to workout generation
 */
export interface PromptConstraints {
    injuries: InjuryConstraint[];
    equipment: string[];
    experienceLevel: 'beginner' | 'intermediate' | 'advanced';
    timeConstraints: {
        maxDuration: number;
        minRestPeriod: number;
    };
}

/**
 * Represents time-based constraints for workout generation
 */
interface TimeConstraints {
    maxDuration: number;
    minRestPeriod: number;
}

/**
 * Service responsible for building AI prompts that generate safe and appropriate workouts.
 * Combines user profiles, preferences, and constraints to create structured prompts.
 */
export class PromptBuilder {
    /**
     * Maps injuries to exercises that should be excluded
     */
    private readonly injuryExerciseMap: Record<string, string[]> = {
        knee: ['squats', 'lunges', 'jump-rope'],
        shoulder: ['push-ups', 'pull-ups', 'military-press'],
        lower_back: ['deadlifts', 'good-mornings', 'bent-over-rows'],
        wrist: ['push-ups', 'planks', 'handstands'],
        ankle: ['running', 'jumping-jacks', 'box-jumps']
    };

    /**
     * Maps injuries to affected muscle groups
     */
    private readonly injuryMuscleMap: Record<string, string[]> = {
        knee: ['quadriceps', 'hamstrings', 'calves'],
        shoulder: ['deltoids', 'rotator cuff', 'upper back'],
        lower_back: ['lower back', 'erector spinae'],
        wrist: ['forearms', 'grip muscles'],
        ankle: ['calves', 'tibialis anterior']
    };

    /**
     * Builds a complete workout prompt by combining user profile, preferences, and equipment
     * @param profile - User profile containing experience level and injuries
     * @param preferences - User's workout preferences including duration and target muscles
     * @param equipment - Available equipment for the workout
     * @returns Structured prompt for the AI service
     */
    public buildWorkoutPrompt(
        profile: UserProfile,
        preferences: WorkoutPreferences,
        equipment: EquipmentSet
    ): AIPrompt {
        const constraints = this.buildConstraints(profile, preferences, equipment);
        
        return {
            profile,
            preferences: {
                ...preferences,
                preferredDuration: preferences.preferredDuration || 30
            },
            trainingPreferences: {
                preferredDays: [],
                preferredTime: 'morning',
                focusAreas: preferences.targetMuscleGroups
            },
            equipment: equipment.available,
            constraints
        };
    }

    /**
     * Builds constraint object based on user profile and preferences
     * @param profile - User profile containing injuries and experience level
     * @param preferences - User's workout preferences
     * @param equipment - Available equipment
     * @returns Structured constraints for the workout
     */
    private buildConstraints(
        profile: UserProfile,
        preferences: WorkoutPreferences,
        equipment: EquipmentSet
    ): PromptConstraints {
        return {
            injuries: this.buildInjuryConstraints(profile.injuries),
            equipment: equipment.available,
            experienceLevel: profile.experienceLevel,
            timeConstraints: {
                maxDuration: preferences.preferredDuration * 60, // Convert to seconds
                minRestPeriod: 30 // Default rest period
            }
        };
    }

    /**
     * Builds injury constraints based on user's reported injuries
     * @param injuries - List of user injuries
     * @returns Array of injury constraints with excluded exercises and muscle groups
     */
    private buildInjuryConstraints(injuries: string[]): InjuryConstraint[] {
        return injuries.map(injury => ({
            injury,
            excludedExercises: this.getExcludedExercises(injury),
            excludedMuscleGroups: this.getAffectedMuscles(injury),
            maxIntensity: 'medium'
        }));
    }

    /**
     * Gets list of exercises that should be excluded for a given injury
     * @param injury - Type of injury
     * @returns Array of exercise names to exclude
     */
    private getExcludedExercises(injury: string): string[] {
        return this.injuryExerciseMap[injury as keyof typeof this.injuryExerciseMap] || [];
    }

    /**
     * Gets list of muscle groups affected by a given injury
     * @param injury - Type of injury
     * @returns Array of affected muscle group names
     */
    private getAffectedMuscles(injury: string): string[] {
        return this.injuryMuscleMap[injury as keyof typeof this.injuryMuscleMap] || [];
    }
} 