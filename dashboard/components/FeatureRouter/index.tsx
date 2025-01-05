import React, { Suspense } from 'react';
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
    // If no feature and no fallback, show error
    if (!feature && !fallbackFeature) {
        return (
            <div className="feature-error">
                <h3>Feature Not Available</h3>
                <p>The requested feature could not be found.</p>
            </div>
        );
    }

    // Use fallback if main feature is not available
    const activeFeature = feature || fallbackFeature;

    // Verify feature is enabled
    if (!activeFeature?.isEnabled()) {
        return (
            <div className="feature-error">
                <h3>Feature Disabled</h3>
                <p>This feature is currently disabled.</p>
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
            <Suspense 
                fallback={
                    <div className="feature-loading">
                        <div className="loading-spinner" />
                        <p>Loading feature...</p>
                    </div>
                }
            >
                {activeFeature.render({ userId: context.userId })}
            </Suspense>
        </ErrorBoundary>
    );
}; 