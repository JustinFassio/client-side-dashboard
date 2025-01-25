import React, { useEffect, useState, useCallback } from 'react';
import { Feature, FeatureContext } from '../../contracts/Feature';
import { ErrorBoundary } from '../ErrorBoundary';

interface FeatureRouterProps {
    feature: Feature | undefined;
    context: FeatureContext;
    fallbackFeature?: Feature;
}

export const FeatureRouter: React.FC<FeatureRouterProps> = ({
    feature,
    context,
    fallbackFeature
}) => {
    const [activeFeature, setActiveFeature] = useState<Feature | undefined>(feature || fallbackFeature);
    const [isInitializing, setIsInitializing] = useState(false);
    const [initError, setInitError] = useState<Error | null>(null);

    // Memoize the initialization function
    const initFeature = useCallback(async (feat: Feature) => {
        if (context.debug) {
            console.log('[FeatureRouter] Initializing feature:', feat.identifier);
        }
        
        setIsInitializing(true);
        setInitError(null);
        
        try {
            await feat.init();
            if (context.debug) {
                console.log('[FeatureRouter] Feature initialized:', feat.identifier);
            }
        } catch (error) {
            console.error('[FeatureRouter] Feature initialization failed:', error);
            setInitError(error instanceof Error ? error : new Error('Feature initialization failed'));
        } finally {
            setIsInitializing(false);
        }
    }, [context.debug]);

    useEffect(() => {
        const newFeature = feature || fallbackFeature;
        if (newFeature && newFeature !== activeFeature) {
            setActiveFeature(newFeature);
            initFeature(newFeature);
        }
    }, [feature, fallbackFeature, initFeature, activeFeature]);

    // If no feature and no fallback, show error
    if (!activeFeature) {
        console.error('[FeatureRouter] No feature available');
        return (
            <div className="feature-error">
                <h3>Feature Not Available</h3>
                <p>The requested feature could not be found.</p>
            </div>
        );
    }

    // Verify feature is enabled
    if (!activeFeature.isEnabled()) {
        console.error('[FeatureRouter] Feature is disabled:', activeFeature.identifier);
        return (
            <div className="feature-error">
                <h3>Feature Disabled</h3>
                <p>This feature is currently disabled.</p>
            </div>
        );
    }

    // Show loading state during initialization
    if (isInitializing) {
        return (
            <div className="feature-loading">
                <h3>Loading Feature</h3>
                <p>Please wait while the feature initializes...</p>
            </div>
        );
    }

    // Show initialization error
    if (initError) {
        return (
            <div className="feature-error">
                <h3>Feature Initialization Failed</h3>
                <p>{initError.message}</p>
                <button onClick={() => window.location.reload()} className="retry-button">
                    Retry
                </button>
            </div>
        );
    }

    if (context.debug) {
        console.log('[FeatureRouter] Rendering feature:', activeFeature.identifier);
    }

    return (
        <ErrorBoundary
            fallback={
                <div className="feature-error">
                    <h3>Rendering Error</h3>
                    <p>An error occurred while rendering the feature.</p>
                </div>
            }
        >
            <div className="feature-container">
                {activeFeature.render({ userId: context.userId })}
            </div>
        </ErrorBoundary>
    );
}; 