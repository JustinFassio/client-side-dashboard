import React from 'react';
import { Feature, FeatureContext, FeatureMetadata } from '../../dashboard/contracts/Feature';
import { OverviewLayout } from './components/layout';

export class OverviewFeature implements Feature {
    public readonly identifier = 'overview';
    public readonly metadata: FeatureMetadata = {
        name: 'Overview',
        description: 'Dashboard overview and summary',
        order: 0
    };

    private context: FeatureContext | null = null;

    public async register(context: FeatureContext): Promise<void> {
        this.context = context;
    }

    public async init(): Promise<void> {
        // Initialize overview data
    }

    public async cleanup(): Promise<void> {
        this.context = null;
    }

    public isEnabled(): boolean {
        return true;
    }

    public render(): JSX.Element | null {
        if (!this.context) {
            return null;
        }

        return React.createElement(OverviewLayout, { context: this.context });
    }
} 