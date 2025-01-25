import React, { useEffect, useState, useCallback } from 'react';
import { useUser } from '../../hooks/useUser';
import { FeatureRegistry } from '../../core/FeatureRegistry';
import { FeatureContext } from '../../contracts/Feature';
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
    const [activeFeature, setActiveFeature] = useState<string>(DEFAULT_FEATURE);
    const [isInitializing, setIsInitializing] = useState(true);
    const [initError, setInitError] = useState<Error | null>(null);

    // Memoize the navigation handler
    const handleNavigation = useCallback(({ identifier }: { identifier: string }) => {
        if (context.debug) {
            console.log('[DashboardShell] Navigation change:', identifier);
        }
        setActiveFeature(identifier);
    }, [context.debug]);

    useEffect(() => {
        const initFeatures = async () => {
            if (!validateDashboardData(window.athleteDashboardData)) {
                console.error('[DashboardShell] Invalid dashboard data structure');
                setInitError(new Error('Invalid dashboard data structure'));
                setIsInitializing(false);
                return;
            }

            try {
                setIsInitializing(true);
                setInitError(null);

                // Initialize all features
                const features = registry.getAllFeatures();
                await Promise.all(features.map(async (feature) => {
                    try {
                        await feature.register(context);
                        if (context.debug) {
                            console.log('[DashboardShell] Feature registered:', feature.identifier);
                        }
                    } catch (error) {
                        console.error(`[DashboardShell] Failed to register feature ${feature.identifier}:`, error);
                        throw error;
                    }
                }));

                // Get feature from URL
                const params = new URLSearchParams(window.location.search);
                const featureFromUrl = params.get('dashboard_feature');
                
                if (featureFromUrl && registry.getFeature(featureFromUrl)) {
                    setActiveFeature(featureFromUrl);
                }

                if (context.debug) {
                    console.log('[DashboardShell] Initialized with:', {
                        featureFromUrl,
                        activeFeature,
                        registeredFeatures: registry.getAllFeatures().map(f => f.identifier),
                        user: user?.id
                    });
                }
            } catch (error) {
                console.error('[DashboardShell] Initialization failed:', error);
                setInitError(error instanceof Error ? error : new Error('Failed to initialize dashboard'));
            } finally {
                setIsInitializing(false);
            }
        };

        initFeatures();

        Events.on('navigation:changed', handleNavigation);

        return () => {
            Events.off('navigation:changed', handleNavigation);
        };
    }, [registry, handleNavigation]); // Remove context and user from dependencies

    // Handle initialization loading state
    if (isInitializing || isUserLoading) {
        return (
            <div className="dashboard-shell">
                <div className="dashboard-loading">
                    <div className="loading-spinner" />
                    <p>Loading dashboard...</p>
                </div>
            </div>
        );
    }

    // Handle initialization error
    if (initError) {
        return (
            <div className="dashboard-shell">
                <div className="dashboard-error">
                    <h3>Dashboard Error</h3>
                    <p>{initError.message}</p>
                    <button onClick={() => window.location.reload()} className="retry-button">
                        Retry
                    </button>
                </div>
            </div>
        );
    }

    // Handle user error state
    if (userError || !user) {
        console.error('[DashboardShell] User error:', userError);
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

    const currentFeature = registry.getFeature(activeFeature);
    const fallbackFeature = activeFeature !== DEFAULT_FEATURE ? registry.getFeature(DEFAULT_FEATURE) : undefined;
    const enabledFeatures = registry.getEnabledFeatures();

    if (context.debug) {
        console.log('[DashboardShell] Rendering with:', {
            activeFeature,
            currentFeature: currentFeature?.identifier,
            fallbackFeature: fallbackFeature?.identifier,
            enabledFeatures: enabledFeatures.map(f => f.identifier)
        });
    }

    // Handle no enabled features
    if (enabledFeatures.length === 0) {
        console.error('[DashboardShell] No enabled features available');
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
                currentFeature={activeFeature}
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