import { WorkoutConfig, WorkoutPreferences, GeneratorSettings } from '../types/workout-types';

const defaultPreferences: WorkoutPreferences = {
    fitnessLevel: 'beginner',
    availableEquipment: [],
    preferredDuration: 30,
    targetMuscleGroups: [],
    healthConditions: [],
    workoutFrequency: 3
};

const defaultSettings: GeneratorSettings = {
    includeWarmup: true,
    includeCooldown: true,
    preferredExerciseTypes: ['strength', 'cardio'],
    maxExercisesPerWorkout: 8,
    restBetweenExercises: 60
};

export const WorkoutConfig: WorkoutConfig = {
    endpoints: {
        base: 'athlete-dashboard/v1/workout',
        generate: 'athlete-dashboard/v1/workout/generate',
        save: 'athlete-dashboard/v1/workout/save',
        history: 'athlete-dashboard/v1/workout/history'
    },
    validation: {
        minDuration: 10,
        maxDuration: 120,
        maxExercises: 15
    },
    defaults: {
        preferences: defaultPreferences,
        settings: defaultSettings
    }
} as const; 