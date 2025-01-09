import React from 'react';
import { Activity } from '../../types';
import { formatDistanceToNow } from 'date-fns';

interface ActivityWidgetProps {
    activities: Activity[];
    className?: string;
}

export const ActivityWidget: React.FC<ActivityWidgetProps> = ({ activities, className }) => {
    return (
        <div className={`activity-widget ${className || ''}`}>
            <h2>Recent Activity</h2>
            <div className="activity-list">
                {activities.length === 0 ? (
                    <p className="no-activities">No recent activities</p>
                ) : (
                    activities.map((activity) => (
                        <div key={activity.id} className="activity-item">
                            <div className="activity-icon">
                                {/* Icon based on activity type */}
                                <span className={`icon-${activity.type}`} />
                            </div>
                            <div className="activity-content">
                                <p className="activity-description">{activity.description}</p>
                                <span className="activity-time">
                                    {formatDistanceToNow(new Date(activity.timestamp), { addSuffix: true })}
                                </span>
                            </div>
                        </div>
                    ))
                )}
            </div>
        </div>
    );
}; 