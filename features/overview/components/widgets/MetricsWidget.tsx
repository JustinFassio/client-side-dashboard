import React from 'react';
import { OverviewStats } from '../../types';

interface MetricsWidgetProps {
    stats: OverviewStats;
    className?: string;
}

export const MetricsWidget: React.FC<MetricsWidgetProps> = ({ stats, className }) => {
    return (
        <div className={`metrics-widget ${className || ''}`}>
            <h2>Performance Metrics</h2>
            <div className="metrics-grid">
                <div className="metric-card">
                    <h3>Workouts Completed</h3>
                    <div className="metric-value">{stats.workouts_completed}</div>
                </div>
                <div className="metric-card">
                    <h3>Active Programs</h3>
                    <div className="metric-value">{stats.active_programs}</div>
                </div>
                <div className="metric-card">
                    <h3>Nutrition Score</h3>
                    <div className="metric-value">{stats.nutrition_score}%</div>
                </div>
            </div>
        </div>
    );
}; 