import React from 'react';
import { Feature, FeatureContext, FeatureMetadata, FeatureRenderProps } from '../../dashboard/contracts/Feature';
import { OverviewLayout } from './components/layout';

export class OverviewFeature implements Feature {
    public readonly identifier = 'overview';
    public readonly metadata: FeatureMetadata = {
        name: 'Overview',
        description: 'Goal Compass',
        order: 0
    };

    private context: FeatureContext | null = null;

    public async register(context: FeatureContext): Promise<void> {
        this.context = context;
        if (context.debug) {
            console.log('Overview feature registered');
        }
    }

    public async init(): Promise<void> {
        if (this.context?.debug) {
            console.log('Overview feature initialized');
        }
    }

    public async cleanup(): Promise<void> {
        this.context = null;
    }

    public isEnabled(): boolean {
        return true;
    }

    public render({ userId }: FeatureRenderProps): JSX.Element | null {
        if (!this.context) {
            console.error('Overview feature context not initialized');
            return null;
        }

        return (
            <OverviewLayout 
                userId={userId}
                context={this.context}
            />
        );
    }
} 