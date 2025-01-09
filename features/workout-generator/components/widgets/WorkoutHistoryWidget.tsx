import React from 'react';
import { WorkoutPlan } from '../../types/workout-types';
import { formatDistanceToNow } from 'date-fns';

interface WorkoutHistoryWidgetProps {
    workouts: WorkoutPlan[];
    className?: string;
}

export const WorkoutHistoryWidget: React.FC<WorkoutHistoryWidgetProps> = ({
    workouts,
    className
}) => {
    return (
        <div className={`workout-history-widget ${className || ''}`}>
            <h2>Workout History</h2>
            <div className="history-list">
                {workouts.length === 0 ? (
                    <p className="no-workouts">No workout history available</p>
                ) : (
                    workouts.map((workout) => (
                        <div key={workout.id} className="history-item">
                            <div className="history-header">
                                <h3>{workout.name}</h3>
                                <span className={`difficulty-badge difficulty-${workout.difficulty}`}>
                                    {workout.difficulty}
                                </span>
                            </div>
                            <div className="history-meta">
                                <span className="meta-item">
                                    <strong>Duration:</strong> {workout.duration} minutes
                                </span>
                                <span className="meta-item">
                                    <strong>Exercises:</strong> {workout.exercises.length}
                                </span>
                                <span className="meta-item">
                                    <strong>Created:</strong>{' '}
                                    {formatDistanceToNow(new Date(workout.createdAt), { addSuffix: true })}
                                </span>
                            </div>
                            <div className="history-goals">
                                <strong>Target Goals:</strong>
                                <div className="goals-list">
                                    {workout.targetGoals.map((goal, index) => (
                                        <span key={index} className="goal-tag">
                                            {goal}
                                        </span>
                                    ))}
                                </div>
                            </div>
                        </div>
                    ))
                )}
            </div>
        </div>
    );
}; 