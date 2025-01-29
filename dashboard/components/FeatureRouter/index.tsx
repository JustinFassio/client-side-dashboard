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

    // Safely log debug messages
    const logDebug = useCallback((message: string) => {
        if (context?.debug) {
            console.log(message);
        }
    }, [context?.debug]);

    // Memoize the initialization function
    const initFeature = useCallback(async (feat: Feature) => {
        logDebug(`[FeatureRouter] Initializing feature: ${feat.identifier}`);
        
        setIsInitializing(true);
        setInitError(null);
        
        try {
            await feat.init();
            logDebug(`[FeatureRouter] Feature initialized: ${feat.identifier}`);
        } catch (error) {
            console.error('[FeatureRouter] Feature initialization failed:', error);
            setInitError(error instanceof Error ? error : new Error('Feature initialization failed'));
        } finally {
            setIsInitializing(false);
        }
    }, [logDebug]);

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

    logDebug(`[FeatureRouter] Rendering feature: ${activeFeature.identifier}`);

    // Ensure context has required properties before rendering
    if (!context || typeof context.userId === 'undefined') {
        console.error('[FeatureRouter] Invalid context:', context);
        return (
            <div className="feature-error">
                <h3>Configuration Error</h3>
                <p>The feature context is not properly configured.</p>
            </div>
        );
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