import React, { Suspense } from 'react';
import { useUser } from '../../hooks/useUser';
import { FeatureRegistry } from '../../core/FeatureRegistry';
import { FeatureContext } from '../../contracts/Feature';
import './DashboardShell.css';

interface DashboardShellProps {
    registry: FeatureRegistry;
    context: FeatureContext;
}

export const DashboardShell: React.FC<DashboardShellProps> = ({ registry, context }) => {
    const { user, isLoading, error } = useUser(context);

    if (isLoading) {
        return (
            <div className="dashboard-shell">
                <div className="dashboard-loading">
                    <p>Loading dashboard...</p>
                </div>
            </div>
        );
    }

    if (error || !user) {
        return (
            <div className="dashboard-shell">
                <div className="dashboard-error">
                    <h3>Dashboard Error</h3>
                    <p>{error || 'Failed to load user data'}</p>
                </div>
            </div>
        );
    }

    const currentFeature = window.athleteDashboardFeature?.name 
        ? registry.getFeature(window.athleteDashboardFeature.name)
        : null;

    if (!currentFeature) {
        return (
            <div className="dashboard-shell">
                <div className="dashboard-error">
                    <h3>Feature Not Found</h3>
                    <p>The requested feature is not available.</p>
                </div>
            </div>
        );
    }

    return (
        <div className="dashboard-shell">
            <main className="dashboard-content">
                <Suspense fallback={<div>Loading feature...</div>}>
                    {currentFeature.render({ userId: user.id })}
                </Suspense>
            </main>
        </div>
    );
}; 