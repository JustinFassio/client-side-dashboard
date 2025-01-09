import React from 'react';
import { FeatureContext } from '../../../../dashboard/contracts/Feature';
import { useWorkout } from '../../contexts/WorkoutContext';
import './styles.css';

interface WorkoutLayoutProps {
    context: FeatureContext;
}

export const WorkoutLayout: React.FC<WorkoutLayoutProps> = ({ context }) => {
    const { state } = useWorkout();
    const { loading, error, currentWorkout, workouts } = state;

    if (loading) {
        return (
            <div className="workout-layout">
                <div className="loading">Loading workouts...</div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="workout-layout">
                <div className="error">Error: {error}</div>
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
                                {exercise.weight && <p>Weight: {exercise.weight}kg</p>}
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

            {workouts.length > 0 && (
                <div className="workout-history">
                    <h2>Workout History</h2>
                    <div className="workouts-list">
                        {workouts.map(workout => (
                            <div key={workout.id} className="workout-item">
                                <h3>{workout.name}</h3>
                                <p>Created: {new Date(workout.createdAt).toLocaleDateString()}</p>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}; 