import React, { useEffect } from 'react';
import { useWorkout } from '../contexts/workout-context';
import { PreferencesWidget } from './widgets/PreferencesWidget';
import { WorkoutPlanWidget } from './widgets/WorkoutPlanWidget';
import { WorkoutHistoryWidget } from './widgets/WorkoutHistoryWidget';
import { LoadingSpinner } from '../../../dashboard/components/LoadingSpinner';
import { ErrorMessage } from '../../../dashboard/components/ErrorMessage';
import './styles.css';

interface WorkoutGeneratorProps {
    userId: number;
}

export const WorkoutGenerator: React.FC<WorkoutGeneratorProps> = ({ userId }) => {
    const { state, actions } = useWorkout();

    useEffect(() => {
        actions.loadHistory();
    }, [actions]);

    if (state.loading) {
        return <LoadingSpinner message="Loading workout data..." />;
    }

    if (state.error) {
        return (
            <ErrorMessage
                error={state.error}
                onRetry={actions.clearError}
            />
        );
    }

    return (
        <div className="workout-generator">
            <header className="workout-header">
                <h1>AI Workout Generator</h1>
                <p>Create personalized workouts based on your preferences and goals</p>
            </header>

            <div className="workout-grid">
                <PreferencesWidget
                    preferences={state.preferences}
                    settings={state.settings}
                    onPreferencesChange={actions.updatePreferences}
                    onSettingsChange={actions.updateSettings}
                    onGenerate={actions.generateWorkout}
                    className="workout-widget"
                />

                {state.currentWorkout && (
                    <WorkoutPlanWidget
                        workout={state.currentWorkout}
                        onSave={actions.saveWorkout}
                        className="workout-widget"
                    />
                )}

                <WorkoutHistoryWidget
                    workouts={state.workoutHistory}
                    className="workout-widget"
                />
            </div>
        </div>
    );
}; 