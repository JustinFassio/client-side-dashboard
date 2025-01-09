import React from 'react';
import { ApiError } from '../../types/api';

interface ErrorMessageProps {
    error: ApiError | Error | null;
    onRetry?: () => void;
}

export const ErrorMessage: React.FC<ErrorMessageProps> = ({ error, onRetry }) => {
    const errorMessage = error instanceof Error ? error.message : 
        'error' in error ? error.message : 
        'An unexpected error occurred';

    return (
        <div className="error-message-container">
            <div className="error-content">
                <h3>Error</h3>
                <p>{errorMessage}</p>
                {onRetry && (
                    <button onClick={onRetry} className="retry-button">
                        Try Again
                    </button>
                )}
            </div>
            <style>{`
                .error-message-container {
                    padding: 2rem;
                    text-align: center;
                }

                .error-content {
                    background: #fff3f3;
                    border: 1px solid #ffcdd2;
                    border-radius: 8px;
                    padding: 1.5rem;
                    max-width: 400px;
                    margin: 0 auto;
                }

                .error-content h3 {
                    color: #d32f2f;
                    margin: 0 0 1rem;
                }

                .error-content p {
                    margin: 0 0 1.5rem;
                    color: #666;
                }

                .retry-button {
                    background: #d32f2f;
                    color: white;
                    border: none;
                    padding: 0.5rem 1rem;
                    border-radius: 4px;
                    cursor: pointer;
                    font-weight: 500;
                    transition: background-color 0.2s;
                }

                .retry-button:hover {
                    background: #b71c1c;
                }
            `}</style>
        </div>
    );
}; 