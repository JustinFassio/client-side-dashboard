import React, { useEffect } from 'react';
import { useUser } from '../../hooks/useUser';
import { FeatureRegistry } from '../../core/FeatureRegistry';
import { FeatureContext, Feature } from '../../contracts/Feature';
import { Events } from '../../core/events';
import { Navigation } from '../Navigation';
import { FeatureRouter } from '../FeatureRouter';
import { validateDashboardData } from '../../utils/validation';
import './DashboardShell.css';

const DEFAULT_FEATURE = 'overview';

interface DashboardShellProps {
    registry: FeatureRegistry;
    context: FeatureContext;
}

export const DashboardShell: React.FC<DashboardShellProps> = ({ registry, context }) => {
    const { user, isLoading: isUserLoading, error: userError } = useUser(context);

    useEffect(() => {
        if (!validateDashboardData(window.athleteDashboardData)) {
            console.error('Invalid dashboard data structure');
            return;
        }

        if (context.debug) {
            console.log('DashboardShell mounted with:', {
                featureData: window.athleteDashboardData.feature,
                registeredFeatures: registry.getAllFeatures().map(f => f.identifier),
                user: user?.id
            });
        }

        const handleFeatureError = ({ identifier, error }: { identifier: string; error: Error }) => {
            console.error(`Feature error (${identifier}):`, error);
        };

        Events.on('feature.error', handleFeatureError);

        return () => {
            Events.off('feature.error', handleFeatureError);
        };
    }, [registry, user, context.debug]);

    // Handle user loading state
    if (isUserLoading) {
        return (
            <div className="dashboard-shell">
                <div className="dashboard-loading">
                    <div className="loading-spinner" />
                    <p>Loading dashboard...</p>
                </div>
            </div>
        );
    }

    // Handle user error state
    if (userError || !user) {
        return (
            <div className="dashboard-shell">
                <div className="dashboard-error">
                    <h3>Dashboard Error</h3>
                    <p>{userError || 'Failed to load user data'}</p>
                    <button onClick={() => window.location.reload()} className="retry-button">
                        Retry
                    </button>
                </div>
            </div>
        );
    }

    const featureName = window.athleteDashboardData.feature?.name || DEFAULT_FEATURE;
    const currentFeature = registry.getFeature(featureName);
    const fallbackFeature = featureName !== DEFAULT_FEATURE ? registry.getFeature(DEFAULT_FEATURE) : undefined;
    const enabledFeatures = registry.getEnabledFeatures();

    if (context.debug) {
        console.log('Current feature:', currentFeature?.identifier);
        console.log('Fallback feature:', fallbackFeature?.identifier);
        console.log('Enabled features:', enabledFeatures.map(f => f.identifier));
    }

    // Handle no enabled features
    if (enabledFeatures.length === 0) {
        return (
            <div className="dashboard-shell">
                <div className="dashboard-error">
                    <h3>No Features Available</h3>
                    <p>No dashboard features are currently enabled.</p>
                </div>
            </div>
        );
    }

    return (
        <div className="dashboard-shell">
            <Navigation 
                features={enabledFeatures} 
                currentFeature={featureName}
            />
            <main className="dashboard-content">
                <FeatureRouter
                    feature={currentFeature}
                    fallbackFeature={fallbackFeature}
                    context={context}
                />
            </main>
        </div>
    );
}; 