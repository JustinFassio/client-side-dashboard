import React, { useEffect, Suspense } from 'react';
import { FeatureRegistry } from '../../core/FeatureRegistry';
import { LoadingState } from '../LoadingState';
import { ErrorBoundary } from '../ErrorBoundary';
import { FeatureData } from '../../types/feature';

const MIN_LOADING_TIME = 500; // ms

interface FeatureRouterProps {
    registry: FeatureRegistry;
}

declare global {
    interface Window {
        athleteDashboardFeature?: FeatureData;
    }
}

export const FeatureRouter: React.FC<FeatureRouterProps> = ({ registry }) => {
    const featureData = window.athleteDashboardFeature;
    const [isLoading, setIsLoading] = React.useState(true);
    const [error, setError] = React.useState<string | null>(null);

    useEffect(() => {
        let loadingTimer: NodeJS.Timeout;

        const initFeature = async () => {
            if (!featureData) {
                setError('No feature data available');
                setIsLoading(false);
                return;
            }

            try {
                const feature = registry.getFeature(featureData.name);
                const startTime = performance.now();
                
                if (!feature) {
                    setError(`Feature "${featureData.name}" not found`);
                    setIsLoading(false);
                    return;
                }

                if (!feature.isEnabled()) {
                    setError(`Feature "${featureData.label}" is currently disabled`);
                    setIsLoading(false);
                    return;
                }

                await feature.init();
                feature.onNavigate?.();

                // Ensure loading state shows for at least MIN_LOADING_TIME
                const elapsed = performance.now() - startTime;
                const remainingTime = Math.max(0, MIN_LOADING_TIME - elapsed);

                loadingTimer = setTimeout(() => {
                    setIsLoading(false);
                    performance.mark('feature-init-end');
                    performance.measure('feature-init-duration', 
                        'feature-init-start', 
                        'feature-init-end'
                    );
                }, remainingTime);

            } catch (err) {
                console.error('Failed to initialize feature:', err);
                setError('Failed to initialize feature');
                setIsLoading(false);
                performance.mark('feature-init-error');
            }
        };

        initFeature();

        return () => {
            clearTimeout(loadingTimer);
            if (featureData) {
                const feature = registry.getFeature(featureData.name);
                if (feature) {
                    performance.mark('feature-cleanup-start');
                    feature.cleanup();
                    performance.mark('feature-cleanup-end');
                    performance.measure('feature-cleanup-duration',
                        'feature-cleanup-start',
                        'feature-cleanup-end'
                    );
                }
            }
        };
    }, [featureData, registry]);

    // Add performance monitoring for render cycles
    React.useEffect(() => {
        const observer = new PerformanceObserver((list) => {
            list.getEntries().forEach((entry) => {
                console.log(`Performance: ${entry.name}:`, {
                    duration: entry.duration.toFixed(2) + 'ms',
                    startTime: entry.startTime.toFixed(2) + 'ms'
                });
            });
        });

        observer.observe({ entryTypes: ['measure'] });
        return () => observer.disconnect();
    }, []);

    if (error) {
        return (
            <div className="feature-error">
                <h2>Error</h2>
                <p>{error}</p>
                <button onClick={() => window.location.reload()}>
                    Try Again
                </button>
            </div>
        );
    }

    if (isLoading || !featureData) {
        return <LoadingState label={`Loading ${featureData?.label || 'feature'}...`} />;
    }

    const feature = registry.getFeature(featureData.name);
    if (!feature) return null;

    return (
        <ErrorBoundary>
            <Suspense fallback={<LoadingState label={`Loading ${featureData.label}...`} />}>
                <div className={`feature-wrapper feature-${featureData.name}`}>
                    {feature.render()}
                </div>
            </Suspense>
        </ErrorBoundary>
    );
}; 