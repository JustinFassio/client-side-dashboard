import React from 'react';
import { Spinner } from '../Spinner';

interface LoadingSpinnerProps {
    message?: string;
}

export const LoadingSpinner: React.FC<LoadingSpinnerProps> = ({ message = 'Loading...' }) => {
    return (
        <div className="loading-spinner-container">
            <Spinner size="large" />
            {message && <p className="loading-message">{message}</p>}
            <style>{`
                .loading-spinner-container {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    padding: 2rem;
                }
                
                .loading-message {
                    margin-top: 1rem;
                    color: #666;
                }
            `}</style>
        </div>
    );
}; 