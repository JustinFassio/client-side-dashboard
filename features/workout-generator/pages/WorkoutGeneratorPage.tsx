import React, { useState, useCallback } from 'react';
import { useWorkout } from '../contexts/WorkoutContext';
import { PreferencesWidget } from '../components/widgets/PreferencesWidget';
import { WorkoutPlanWidget } from '../components/widgets/WorkoutPlanWidget';
import { WorkoutHistoryWidget } from '../components/widgets/WorkoutHistoryWidget';
import { WorkoutPreferences, GeneratorSettings, WorkoutPlan } from '../types/workout-types';
import { WorkoutConfig } from '../config';
import { WorkoutEvent } from '../events';
import styled from 'styled-components';

const StyledPage = styled.div`
    padding: 2rem;
    max-width: 1200px;
    margin: 0 auto;
`;

const PageHeader = styled.div`
    text-align: center;
    margin-bottom: 2rem;

    h1 {
        font-size: 2.5rem;
        color: #2c3e50;
        margin-bottom: 0.5rem;
    }

    p {
        color: #7f8c8d;
        font-size: 1.1rem;
    }
`;

const ErrorMessage = styled.div`
    background-color: #fee2e2;
    border: 1px solid #ef4444;
    color: #b91c1c;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 2rem;
`;

const PageContent = styled.div`
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 2rem;
`;

const PreferencesSection = styled.div`
    grid-column: 1;
`;

const WorkoutSection = styled.div`
    grid-column: 2;
    min-height: 400px;
`;

const HistorySection = styled.div`
    grid-column: 1 / -1;
    margin-top: 2rem;
`;

const LoadingIndicator = styled.div`
    display: flex;
    align-items: center;
    justify-content: center;
    height: 400px;
    background-color: #f8fafc;
    border-radius: 0.5rem;
    color: #64748b;
    font-size: 1.1rem;
`;

const NoWorkoutMessage = styled.div`
    display: flex;
    align-items: center;
    justify-content: center;
    height: 400px;
    background-color: #f8fafc;
    border-radius: 0.5rem;
    color: #64748b;
    font-size: 1.1rem;
    text-align: center;
    padding: 2rem;
`;

const StyledWidget = styled.div`
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
    padding: 1.5rem;
`;

export const WorkoutGeneratorPage: React.FC = () => {
    const { state, dispatch } = useWorkout();
    const { isLoading: loading, error, currentWorkout, workoutHistory: workouts } = state;

    const [preferences, setPreferences] = useState<WorkoutPreferences>(
        WorkoutConfig.defaults.preferences
    );
    const [settings, setSettings] = useState<GeneratorSettings>(
        WorkoutConfig.defaults.settings
    );

    const handlePreferencesChange = useCallback((newPreferences: WorkoutPreferences) => {
        setPreferences(newPreferences);
    }, []);

    const handleSettingsChange = useCallback((newSettings: GeneratorSettings) => {
        setSettings(newSettings);
    }, []);

    const handleGenerate = useCallback(async (prefs: WorkoutPreferences, sets: GeneratorSettings) => {
        try {
            dispatch({ type: WorkoutEvent.GENERATE_REQUEST });
            // The actual API call will be handled by the context/reducer
        } catch (err) {
            if (err instanceof Error) {
                console.error('Error generating workout:', err);
                dispatch({ type: WorkoutEvent.GENERATE_ERROR, payload: err.message });
            }
        }
    }, [dispatch]);

    const handleSaveWorkout = useCallback(async (workout: WorkoutPlan) => {
        try {
            // The actual API call will be handled by the context/reducer
            dispatch({ type: WorkoutEvent.GENERATE_SUCCESS, payload: workout });
        } catch (err) {
            if (err instanceof Error) {
                console.error('Error saving workout:', err);
                dispatch({ type: WorkoutEvent.GENERATE_ERROR, payload: err.message });
            }
        }
    }, [dispatch]);

    return (
        <StyledPage>
            <PageHeader>
                <h1>AI Workout Generator</h1>
                <p>Generate personalized workouts based on your preferences and goals</p>
            </PageHeader>

            {error && (
                <ErrorMessage>
                    {error.message}
                </ErrorMessage>
            )}

            <PageContent>
                <PreferencesSection>
                    <PreferencesWidget
                        preferences={preferences}
                        settings={settings}
                        onPreferencesChange={handlePreferencesChange}
                        onSettingsChange={handleSettingsChange}
                        onGenerate={handleGenerate}
                        className="preferences-widget"
                    />
                </PreferencesSection>

                <WorkoutSection>
                    {loading ? (
                        <LoadingIndicator>
                            Generating your personalized workout...
                        </LoadingIndicator>
                    ) : currentWorkout ? (
                        <WorkoutPlanWidget
                            workout={currentWorkout as WorkoutPlan}
                            onSave={handleSaveWorkout}
                            className="workout-widget"
                        />
                    ) : (
                        <NoWorkoutMessage>
                            Configure your preferences and click "Generate Workout" to create a personalized workout plan.
                        </NoWorkoutMessage>
                    )}
                </WorkoutSection>

                <HistorySection>
                    <WorkoutHistoryWidget
                        workouts={workouts as WorkoutPlan[]}
                        className="history-widget"
                    />
                </HistorySection>
            </PageContent>
        </StyledPage>
    );
}; 