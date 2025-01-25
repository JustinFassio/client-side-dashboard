import { DashboardError } from '../../../dashboard/types';

export interface Exercise {
    id: string;
    name: string;
    type: 'strength' | 'cardio' | 'flexibility' | 'warmup';
    equipment: string[];
    targetMuscles: string[];
    difficulty: 'beginner' | 'intermediate' | 'advanced';
    instructions: string;
    duration?: number; // in seconds
    sets?: number;
    reps?: number;
    restPeriod?: number; // in seconds
}

export interface WorkoutPlan {
    id?: string;
    name?: string;
    description?: string;
    difficulty: string;
    duration: number;
    exercises: Exercise[];
    targetGoals: string[];
    equipment?: string[];
    createdAt?: string;
    updatedAt?: string;
    userId?: number;
    preferences?: {
        maxExercises: number;
        minRestPeriod: number;
    };
}

export interface WorkoutPreferences {
    fitnessLevel: 'beginner' | 'intermediate' | 'advanced';
    availableEquipment: string[];
    preferredDuration: number; // in minutes
    targetMuscleGroups: string[];
    healthConditions: string[];
    workoutFrequency: number; // sessions per week
    maxExercises?: number;
    minRestPeriod?: number;
}

export interface GeneratorSettings {
    includeWarmup: boolean;
    includeCooldown: boolean;
    preferredExerciseTypes: ('strength' | 'cardio' | 'flexibility')[];
    maxExercisesPerWorkout: number;
    restBetweenExercises: number; // in seconds
}

export interface WorkoutState {
    isLoading: boolean;
    error: DashboardError | null;
    preferences: WorkoutPreferences | null;
    settings: GeneratorSettings | null;
    currentWorkout: WorkoutPlan | null;
    workoutHistory: WorkoutPlan[];
}

export type WorkoutStatus = 'pending' | 'generating' | 'completed' | 'failed';

export enum WorkoutErrorCode {
    GENERATION_FAILED = 'GENERATION_FAILED',
    VALIDATION_FAILED = 'VALIDATION_FAILED',
    RATE_LIMIT_EXCEEDED = 'RATE_LIMIT_EXCEEDED',
    INVALID_INPUT = 'INVALID_INPUT',
    SAFETY_VALIDATION_FAILED = 'SAFETY_VALIDATION_FAILED',
    MODIFICATION_FAILED = 'MODIFICATION_FAILED',
    SAVE_FAILED = 'SAVE_FAILED',
    HISTORY_FETCH_FAILED = 'HISTORY_FETCH_FAILED',
    NO_ALTERNATIVES = 'NO_ALTERNATIVES',
    ALTERNATIVE_FAILED = 'ALTERNATIVE_FAILED',
    WORKOUT_NOT_FOUND = 'WORKOUT_NOT_FOUND'
}

export class WorkoutError extends Error {
    constructor(
        message: string,
        public readonly code: WorkoutErrorCode
    ) {
        super(message);
        this.name = 'WorkoutError';
    }
}

export interface WorkoutValidation {
    isValid: boolean;
    errors: Record<string, string[]>;
}

export interface WorkoutConfig {
    endpoints: {
        base: string;
        generate: string;
        save: string;
        history: string;
    };
    validation: {
        minDuration: number;
        maxDuration: number;
        maxExercises: number;
    };
    defaults: {
        preferences: WorkoutPreferences;
        settings: GeneratorSettings;
    };
}

export interface UserProfile {
    id: string;
    injuries: string[];
    heightCm: number;
    weightKg: number;
    experienceLevel: 'beginner' | 'intermediate' | 'advanced';
}

export interface TrainingPreferences {
    preferredDays: string[];
    preferredTime: 'morning' | 'afternoon' | 'evening';
    focusAreas: string[];
}

export interface EquipmentSet {
    available: string[];
    preferred: string[];
}

export interface AIPrompt {
    profile: UserProfile;
    preferences: WorkoutPreferences;
    trainingPreferences: TrainingPreferences;
    equipment: string[];
    constraints: {
        injuries: any[];
        equipment: string[];
        experienceLevel: 'beginner' | 'intermediate' | 'advanced';
        timeConstraints: {
            maxDuration: number;
            minRestPeriod: number;
        };
    };
}

export interface ExerciseConstraints {
    injuries: string[];
    equipment: string[];
    experienceLevel: 'beginner' | 'intermediate' | 'advanced';
    maxIntensity?: 'low' | 'medium' | 'high';
}

export interface WorkoutModification {
    exerciseId: string;
    action: 'replace' | 'remove' | 'modify';
    replacement?: Exercise;
    modifications?: {
        sets?: number;
        reps?: number;
        weight?: number;
        duration?: number;
        intensity?: number;
    };
}

export interface HistoryFilters {
    startDate?: string;
    endDate?: string;
    type?: string[];
    difficulty?: ('beginner' | 'intermediate' | 'advanced')[];
    equipment?: string[];
    limit?: number;
}

export interface ValidationResult {
    isValid: boolean;
    errors: Record<string, string[]>;
}

export interface WorkoutRequest {
    userId: number;
    preferences: WorkoutPreferences;
    settings: GeneratorSettings;
} 