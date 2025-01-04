import React from 'react';

interface LoadingStateProps {
    label?: string;
}

export const LoadingState: React.FC<LoadingStateProps> = ({ label }) => {
    React.useEffect(() => {
        performance.mark('feature-loading-start');
        return () => {
            performance.mark('feature-loading-end');
            performance.measure('feature-loading-duration', 
                'feature-loading-start', 
                'feature-loading-end'
            );
        };
    }, []);

    return (
        <div className="feature-loading">
            <div className="loading-spinner"></div>
            <p>{label || 'Loading...'}</p>
        </div>
    );
}; 