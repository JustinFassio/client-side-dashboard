import React, { useEffect, useState } from 'react';
import { OverviewService, OverviewData } from '../services/OverviewService';
import './OverviewLayout.css';

interface OverviewLayoutProps {
    userId: number;
    service: OverviewService;
    onError: (error: Error) => void;
}

export const OverviewLayout: React.FC<OverviewLayoutProps> = ({
    userId,
    service,
    onError
}) => {
    const [data, setData] = useState<OverviewData | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchData = async () => {
            try {
                setLoading(true);
                const overviewData = await service.getOverviewData(userId);
                setData(overviewData);
            } catch (error) {
                onError(error as Error);
            } finally {
                setLoading(false);
            }
        };

        fetchData();
    }, [userId, service, onError]);

    if (loading) {
        return (
            <div className="overview-layout">
                <div className="overview-loading">Loading overview data...</div>
            </div>
        );
    }

    if (!data) {
        return (
            <div className="overview-layout">
                <div className="overview-error">Failed to load overview data</div>
            </div>
        );
    }

    const handleGoalUpdate = async (goalId: number, progress: number) => {
        try {
            await service.updateGoal(goalId, progress);
            const updatedData = await service.getOverviewData(userId);
            setData(updatedData);
        } catch (error) {
            onError(error as Error);
        }
    };

    const handleActivityDismiss = async (activityId: number) => {
        try {
            await service.dismissActivity(activityId);
            const updatedData = await service.getOverviewData(userId);
            setData(updatedData);
        } catch (error) {
            onError(error as Error);
        }
    };

    return (
        <div className="overview-layout">
            <section className="overview-stats">
                <div className="stat-card">
                    <h3>Workouts Completed</h3>
                    <div className="stat-value">{data.stats.workouts_completed}</div>
                </div>
                <div className="stat-card">
                    <h3>Active Programs</h3>
                    <div className="stat-value">{data.stats.active_programs}</div>
                </div>
                <div className="stat-card">
                    <h3>Nutrition Score</h3>
                    <div className="stat-value">{data.stats.nutrition_score}</div>
                </div>
            </section>

            <section className="overview-goals">
                <h2>Goals</h2>
                <div className="goals-list">
                    {data.goals.map(goal => (
                        <div key={goal.id} className="goal-card">
                            <h4>{goal.title}</h4>
                            <div className="goal-progress">
                                <div 
                                    className="progress-bar"
                                    style={{ width: `${goal.progress}%` }}
                                />
                            </div>
                            <div className="goal-details">
                                <span>{goal.progress}% Complete</span>
                                <span>Target: {goal.target_date}</span>
                            </div>
                        </div>
                    ))}
                </div>
            </section>

            <section className="overview-activity">
                <h2>Recent Activity</h2>
                <div className="activity-list">
                    {data.recent_activity.map(activity => (
                        <div key={activity.id} className="activity-card">
                            <div className="activity-content">
                                <span className="activity-type">{activity.type}</span>
                                <h4>{activity.title}</h4>
                                <span className="activity-date">{activity.date}</span>
                            </div>
                            <button
                                className="dismiss-button"
                                onClick={() => handleActivityDismiss(activity.id)}
                                aria-label="Dismiss activity"
                            >
                                ×
                            </button>
                        </div>
                    ))}
                </div>
            </section>
        </div>
    );
}; 