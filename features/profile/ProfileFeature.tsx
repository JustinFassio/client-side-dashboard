import React from 'react';
import { Feature, FeatureContext, FeatureMetadata, FeatureRenderProps } from '../../dashboard/contracts/Feature';
import { ProfileLayout } from './components/layout';
import { ProfileProvider } from './context/ProfileContext';

export class ProfileFeature implements Feature {
    public readonly identifier = 'profile';
    public readonly metadata: FeatureMetadata = {
        name: 'Profile',
        description: 'Manage your athlete profile',
        order: 1
    };

    private context: FeatureContext | null = null;

    public async register(context: FeatureContext): Promise<void> {
        this.context = context;
        if (context.debug) {
            console.log('Profile feature registered');
        }
    }

    public async init(): Promise<void> {
        if (this.context?.debug) {
            console.log('Profile feature initialized');
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
            console.error('Profile feature context not initialized');
            return null;
        }

        return (
            <ProfileProvider userId={userId}>
                <ProfileLayout 
                    userId={userId}
                    context={this.context}
                />
            </ProfileProvider>
        );
    }
} 