import React from 'react';
import { FeatureContext } from '../../../../dashboard/contracts/Feature';
import { useWorkout } from '../../contexts/WorkoutContext';
import './styles.css';

interface WorkoutLayoutProps {
    context: FeatureContext;
}

export const WorkoutLayout: React.FC<WorkoutLayoutProps> = ({ context }) => {
    const { state } = useWorkout();
    const { isLoading, error, currentWorkout, workoutHistory } = state;

    if (isLoading) {
        return (
            <div className="workout-layout">
                <div className="loading">Loading workouts...</div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="workout-layout">
                <div className="error">Error: {error.message}</div>
            </div>
        );
    }

    return (
        <div className="workout-layout">
            <h1>AI Workout Generator</h1>
            
            {currentWorkout ? (
                <div className="current-workout">
                    <h2>{currentWorkout.name}</h2>
                    <div className="exercises">
                        {currentWorkout.exercises.map((exercise, index) => (
                            <div key={index} className="exercise">
                                <h3>{exercise.name}</h3>
                                <p>Sets: {exercise.sets}</p>
                                <p>Reps: {exercise.reps}</p>
                                {exercise.duration && <p>Duration: {exercise.duration}s</p>}
                                {exercise.restPeriod && <p>Rest: {exercise.restPeriod}s</p>}
                                <p>Type: {exercise.type}</p>
                                <p>Difficulty: {exercise.difficulty}</p>
                            </div>
                        ))}
                    </div>
                </div>
            ) : (
                <div className="no-workout">
                    <p>No workout generated yet.</p>
                    <button className="generate-button">
                        Generate Workout
                    </button>
                </div>
            )}

            {workoutHistory.length > 0 && (
                <div className="workout-history">
                    <h2>Workout History</h2>
                    <div className="workouts-list">
                        {workoutHistory.map(workout => (
                            <div key={workout.id} className="workout-item">
                                <h3>{workout.name}</h3>
                                <p>Created: {workout.createdAt && new Date(workout.createdAt).toLocaleDateString()}</p>
                                <p>Duration: {workout.duration} minutes</p>
                                <p>Difficulty: {workout.difficulty}</p>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}; 