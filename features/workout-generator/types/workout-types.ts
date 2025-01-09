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

export interface WorkoutResponse {
    success: boolean;
    data?: WorkoutPlan;
    error?: {
        code: string;
        message: string;
    };
}

export type WorkoutStatus = 'pending' | 'generating' | 'completed' | 'failed'; 