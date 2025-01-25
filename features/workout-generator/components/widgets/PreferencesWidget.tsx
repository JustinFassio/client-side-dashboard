import React, { useState } from 'react';
import { WorkoutPreferences, GeneratorSettings } from '../../types/workout-types';

interface PreferencesWidgetProps {
    preferences: WorkoutPreferences | null;
    settings: GeneratorSettings | null;
    onPreferencesChange: (preferences: WorkoutPreferences) => void;
    onSettingsChange: (settings: GeneratorSettings) => void;
    onGenerate: (preferences: WorkoutPreferences, settings: GeneratorSettings) => void;
    className?: string;
}

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

export const PreferencesWidget: React.FC<PreferencesWidgetProps> = ({
    preferences,
    settings,
    onPreferencesChange,
    onSettingsChange,
    onGenerate,
    className
}) => {
    const [localPreferences, setLocalPreferences] = useState<WorkoutPreferences>(
        preferences || defaultPreferences
    );
    const [localSettings, setLocalSettings] = useState<GeneratorSettings>(
        settings || defaultSettings
    );

    const handlePreferencesChange = (field: keyof WorkoutPreferences, value: any) => {
        const updated = { ...localPreferences, [field]: value };
        setLocalPreferences(updated);
        onPreferencesChange(updated);
    };

    const handleSettingsChange = (field: keyof GeneratorSettings, value: any) => {
        const updated = { ...localSettings, [field]: value };
        setLocalSettings(updated);
        onSettingsChange(updated);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        onGenerate(localPreferences, localSettings);
    };

    return (
        <div className={`preferences-widget ${className || ''}`}>
            <h2>Workout Preferences</h2>
            <form onSubmit={handleSubmit}>
                <div className="preferences-section">
                    <h3>Basic Preferences</h3>
                    <div className="form-group">
                        <label htmlFor="fitnessLevel">Fitness Level</label>
                        <select
                            id="fitnessLevel"
                            value={localPreferences.fitnessLevel}
                            onChange={(e) => handlePreferencesChange('fitnessLevel', e.target.value)}
                        >
                            <option value="beginner">Beginner</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="advanced">Advanced</option>
                        </select>
                    </div>

                    <div className="form-group">
                        <label htmlFor="preferredDuration">Workout Duration (minutes)</label>
                        <input
                            type="number"
                            id="preferredDuration"
                            value={localPreferences.preferredDuration}
                            onChange={(e) => handlePreferencesChange('preferredDuration', parseInt(e.target.value))}
                            min={15}
                            max={120}
                        />
                    </div>

                    <div className="form-group">
                        <label htmlFor="workoutFrequency">Workouts per Week</label>
                        <input
                            type="number"
                            id="workoutFrequency"
                            value={localPreferences.workoutFrequency}
                            onChange={(e) => handlePreferencesChange('workoutFrequency', parseInt(e.target.value))}
                            min={1}
                            max={7}
                        />
                    </div>
                </div>

                <div className="preferences-section">
                    <h3>Workout Settings</h3>
                    <div className="form-group">
                        <label>
                            <input
                                type="checkbox"
                                checked={localSettings.includeWarmup}
                                onChange={(e) => handleSettingsChange('includeWarmup', e.target.checked)}
                            />
                            Include Warm-up
                        </label>
                    </div>

                    <div className="form-group">
                        <label>
                            <input
                                type="checkbox"
                                checked={localSettings.includeCooldown}
                                onChange={(e) => handleSettingsChange('includeCooldown', e.target.checked)}
                            />
                            Include Cool-down
                        </label>
                    </div>

                    <div className="form-group">
                        <label htmlFor="maxExercises">Maximum Exercises</label>
                        <input
                            type="number"
                            id="maxExercises"
                            value={localSettings.maxExercisesPerWorkout}
                            onChange={(e) => handleSettingsChange('maxExercisesPerWorkout', parseInt(e.target.value))}
                            min={4}
                            max={15}
                        />
                    </div>

                    <div className="form-group">
                        <label htmlFor="restPeriod">Rest Between Exercises (seconds)</label>
                        <input
                            type="number"
                            id="restPeriod"
                            value={localSettings.restBetweenExercises}
                            onChange={(e) => handleSettingsChange('restBetweenExercises', parseInt(e.target.value))}
                            min={30}
                            max={180}
                            step={15}
                        />
                    </div>
                </div>

                <button type="submit" className="generate-button">
                    Generate Workout
                </button>
            </form>
        </div>
    );
}; 