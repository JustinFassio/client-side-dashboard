import React from 'react';
import { WorkoutPlan, Exercise } from '../../types/workout-types';
import { Button } from '../../../../dashboard/components/Button';

interface WorkoutPlanWidgetProps {
    workout: WorkoutPlan;
    onSave: (workout: WorkoutPlan) => void;
    className?: string;
}

const ExerciseCard: React.FC<{ exercise: Exercise }> = ({ exercise }) => (
    <div className="exercise-card">
        <div className="exercise-header">
            <h4>{exercise.name}</h4>
            <span className={`exercise-type type-${exercise.type}`}>
                {exercise.type}
            </span>
        </div>
        <p className="exercise-instructions">{exercise.instructions}</p>
        <div className="exercise-details">
            {exercise.sets && (
                <span className="detail">
                    <strong>Sets:</strong> {exercise.sets}
                </span>
            )}
            {exercise.reps && (
                <span className="detail">
                    <strong>Reps:</strong> {exercise.reps}
                </span>
            )}
            {exercise.duration && (
                <span className="detail">
                    <strong>Duration:</strong> {exercise.duration}s
                </span>
            )}
            {exercise.restPeriod && (
                <span className="detail">
                    <strong>Rest:</strong> {exercise.restPeriod}s
                </span>
            )}
        </div>
        <div className="exercise-meta">
            <div className="equipment">
                <strong>Equipment:</strong>
                <ul>
                    {exercise.equipment.map((item) => (
                        <li key={item}>{item}</li>
                    ))}
                </ul>
            </div>
            <div className="muscles">
                <strong>Target Muscles:</strong>
                <ul>
                    {exercise.targetMuscles.map((muscle) => (
                        <li key={muscle}>{muscle}</li>
                    ))}
                </ul>
            </div>
        </div>
    </div>
);

export const WorkoutPlanWidget: React.FC<WorkoutPlanWidgetProps> = ({
    workout,
    onSave,
    className
}) => {
    return (
        <div className={`workout-plan-widget ${className || ''}`}>
            <div className="workout-header">
                <div className="workout-title">
                    <h2>{workout.name}</h2>
                    <span className={`difficulty-badge difficulty-${workout.difficulty}`}>
                        {workout.difficulty}
                    </span>
                </div>
                <Button
                    variant="primary"
                    onClick={() => onSave(workout)}
                >
                    Save Workout
                </Button>
            </div>

            <div className="workout-meta">
                <span className="meta-item">
                    <strong>Duration:</strong> {workout.duration} minutes
                </span>
                <span className="meta-item">
                    <strong>Equipment Needed:</strong>{' '}
                    {workout.equipment.join(', ')}
                </span>
                <span className="meta-item">
                    <strong>Target Goals:</strong>{' '}
                    {workout.targetGoals.join(', ')}
                </span>
            </div>

            <div className="workout-description">
                <p>{workout.description}</p>
            </div>

            <div className="exercises-list">
                <h3>Exercises</h3>
                {workout.exercises.map((exercise) => (
                    <ExerciseCard key={exercise.id} exercise={exercise} />
                ))}
            </div>
        </div>
    );
}; 