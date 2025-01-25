import React from 'react';
import { Goal } from '../../types';

interface GoalsWidgetProps {
    goals: Goal[];
    className?: string;
}

export const GoalsWidget: React.FC<GoalsWidgetProps> = ({ goals, className }) => {
    return (
        <div className={`goals-widget ${className || ''}`}>
            <h2>Goals</h2>
            <div className="goals-list">
                {goals.length === 0 ? (
                    <p className="no-goals">No active goals</p>
                ) : (
                    goals.map((goal) => (
                        <div key={goal.id} className="goal-item">
                            <div className="goal-header">
                                <h3>{goal.title}</h3>
                                <span className={`goal-status status-${goal.status}`}>
                                    {goal.status}
                                </span>
                            </div>
                            <p className="goal-description">{goal.description}</p>
                            <div className="goal-progress">
                                <div 
                                    className="progress-bar"
                                    style={{ width: `${goal.progress}%` }}
                                />
                                <span className="progress-text">{goal.progress}%</span>
                            </div>
                            <div className="goal-footer">
                                <span className="goal-date">
                                    Target: {new Date(goal.target_date).toLocaleDateString()}
                                </span>
                            </div>
                        </div>
                    ))
                )}
            </div>
        </div>
    );
}; 