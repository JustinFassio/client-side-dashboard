import React from 'react';
import { FeatureContext } from './types';

export interface Feature {
    readonly id: string;
    readonly name: string;
    readonly description: string;
    isInitialized: boolean;
    initialize(context: FeatureContext): Promise<void>;
    render(): React.ReactElement | null;
    cleanup(): void;
} 