import React from 'react';

export interface SpinnerProps extends React.HTMLAttributes<HTMLDivElement> {
    size?: 'small' | 'medium' | 'large';
}

export declare const Spinner: React.FC<SpinnerProps>; 