import React from 'react';
import './styles.css';

interface WorkoutGeneratorProps {
    userId: number;
}

export const WorkoutGenerator: React.FC<WorkoutGeneratorProps> = ({ userId }) => {
    return (
        <div className="workout-generator">
            <header className="workout-header">
                <h1>AI Workout Generator</h1>
                <p>Create personalized workouts based on your preferences and goals</p>
            </header>

            <div className="workout-grid">
                {/* Placeholder for future widgets */}
                <div className="workout-widget preferences-widget">
                    <h3>Workout Preferences</h3>
                    <p>Configure your workout preferences here</p>
                </div>

                <div className="workout-widget workout-plan-widget">
                    <h3>Current Workout</h3>
                    <p>Your generated workout will appear here</p>
                </div>

                <div className="workout-widget history-widget">
                    <h3>Workout History</h3>
                    <p>Your past workouts will be listed here</p>
                </div>
            </div>
        </div>
    );
}; 