import { DashboardError } from '../../../dashboard/types';

export interface Exercise {
    id: string;
    name: string;
    type: 'strength' | 'cardio' | 'flexibility';
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
    id: string;
    name: string;
    description: string;
    difficulty: 'beginner' | 'intermediate' | 'advanced';
    duration: number; // in minutes
    exercises: Exercise[];
    targetGoals: string[];
    equipment: string[];
    createdAt: string;
    updatedAt: string;
}

export interface WorkoutPreferences {
    fitnessLevel: 'beginner' | 'intermediate' | 'advanced';
    availableEquipment: string[];
    preferredDuration: number; // in minutes
    targetMuscleGroups: string[];
    healthConditions: string[];
    workoutFrequency: number; // sessions per week
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

export interface WorkoutError extends DashboardError {
    code: WorkoutErrorCode;
}

export enum WorkoutErrorCode {
    GENERATION_FAILED = 'GENERATION_FAILED',
    INVALID_PREFERENCES = 'INVALID_PREFERENCES',
    INVALID_SETTINGS = 'INVALID_SETTINGS',
    SAVE_FAILED = 'SAVE_FAILED',
    LOAD_FAILED = 'LOAD_FAILED',
    NETWORK_ERROR = 'NETWORK_ERROR',
    UNKNOWN_ERROR = 'UNKNOWN_ERROR'
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