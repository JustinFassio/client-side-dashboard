import React, { useEffect, useState } from 'react';
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

    useEffect(() => {
        if (context.debug) {
            console.log('[FeatureRouter] Feature changed:', {
                feature: feature?.identifier,
                fallback: fallbackFeature?.identifier
            });
        }
        setActiveFeature(feature || fallbackFeature);
    }, [feature, fallbackFeature, context.debug]);

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