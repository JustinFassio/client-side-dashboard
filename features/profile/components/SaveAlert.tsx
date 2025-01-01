import React from 'react';

interface SaveAlertProps {
    success: boolean;
    error: string | null;
    onDismiss: () => void;
}

export function SaveAlert({ success, error, onDismiss }: SaveAlertProps) {
    if (!success && !error) {
        return null;
    }

    return (
        <div className={`save-alert ${success ? 'success' : 'error'}`}>
            <div className="alert-content">
                {success && (
                    <div className="success-message">
                        Profile saved successfully!
                    </div>
                )}
                {error && (
                    <div className="error-message">
                        {error}
                    </div>
                )}
            </div>
            <button className="dismiss-button" onClick={onDismiss}>
                ✕
            </button>

            <style jsx>{`
                .save-alert {
                    position: relative;
                    padding: 15px 40px 15px 15px;
                    margin-bottom: 20px;
                    border-radius: 4px;
                    animation: slideIn 0.3s ease-out;
                }

                .success {
                    background-color: #d4edda;
                    border: 1px solid #c3e6cb;
                    color: #155724;
                }

                .error {
                    background-color: #f8d7da;
                    border: 1px solid #f5c6cb;
                    color: #721c24;
                }

                .alert-content {
                    margin-right: 20px;
                }

                .success-message,
                .error-message {
                    margin: 0;
                    font-size: 14px;
                }

                .dismiss-button {
                    position: absolute;
                    top: 50%;
                    right: 10px;
                    transform: translateY(-50%);
                    background: none;
                    border: none;
                    color: inherit;
                    font-size: 18px;
                    cursor: pointer;
                    padding: 5px;
                    opacity: 0.7;
                    transition: opacity 0.2s;
                }

                .dismiss-button:hover {
                    opacity: 1;
                }

                @keyframes slideIn {
                    from {
                        transform: translateY(-20px);
                        opacity: 0;
                    }
                    to {
                        transform: translateY(0);
                        opacity: 1;
                    }
                }
            `}</style>
        </div>
    );
} 