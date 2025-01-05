import React from 'react';
import { FeatureContext } from '../../../../dashboard/contracts/Feature';
import './OverviewLayout.css';

interface OverviewLayoutProps {
    userId: number;
    context: FeatureContext;
}

export const OverviewLayout: React.FC<OverviewLayoutProps> = ({
    userId,
    context
}) => {
    return (
        <div className="overview-layout">
            <header className="overview-header">
                <h1>Dashboard Overview</h1>
                <p>Welcome to your athlete dashboard overview.</p>
            </header>

            <div className="overview-content">
                <section className="overview-section">
                    <h2>Quick Stats</h2>
                    <div className="stats-grid">
                        <div className="stat-card">
                            <h3>Workouts</h3>
                            <p className="stat-value">0</p>
                        </div>
                        <div className="stat-card">
                            <h3>Active Goals</h3>
                            <p className="stat-value">0</p>
                        </div>
                        <div className="stat-card">
                            <h3>Progress</h3>
                            <p className="stat-value">0%</p>
                        </div>
                    </div>
                </section>

                <section className="overview-section">
                    <h2>Recent Activity</h2>
                    <div className="activity-list">
                        <p className="empty-state">No recent activity to display.</p>
                    </div>
                </section>

                <section className="overview-section">
                    <h2>Next Steps</h2>
                    <ul className="next-steps-list">
                        <li>Complete your profile</li>
                        <li>Set your fitness goals</li>
                        <li>Schedule your first workout</li>
                    </ul>
                </section>
            </div>

            {context.debug && (
                <div className="debug-info">
                    <h3>Debug Information</h3>
                    <pre>
                        {JSON.stringify({ userId, context }, null, 2)}
                    </pre>
                </div>
            )}
        </div>
    );
}; 