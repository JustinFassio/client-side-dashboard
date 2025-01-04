import React from 'react';

export interface SpinnerProps extends React.HTMLAttributes<HTMLDivElement> {
    size?: 'small' | 'medium' | 'large';
}

export const Spinner: React.FC<SpinnerProps> = ({ size = 'medium', className = '', ...props }) => {
    const sizeClass = {
        small: 'w-4 h-4',
        medium: 'w-8 h-8',
        large: 'w-12 h-12'
    }[size];

    return (
        <div
            className={`inline-block animate-spin rounded-full border-2 border-solid border-current border-r-transparent motion-reduce:animate-[spin_1.5s_linear_infinite] ${sizeClass} ${className}`}
            role="status"
            {...props}
        >
            <span className="sr-only">Loading...</span>
        </div>
    );
}; 