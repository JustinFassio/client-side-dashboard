import React from 'react';
import { Feature, FeatureContext, FeatureMetadata } from '../../dashboard/contracts/Feature';
import { ProfileEvent } from './events';
import { ProfileProvider } from './context/ProfileContext';
import { ProfileLayout } from './components/layout';
import { UserProvider } from '../user/context/UserContext';

/**
 * ProfileFeature implements the athlete profile management functionality.
 * Responsible for:
 * - Profile data management through ProfileContext
 * - User authentication through UserContext
 * - Profile UI rendering with ProfileLayout
 * - Event handling and navigation
 */
export class ProfileFeature implements Feature {
    public readonly identifier = 'profile';
    public readonly metadata: FeatureMetadata = {
        name: 'Profile',
        description: 'Personalize your journey',
        order: 1
    };

    private context: FeatureContext | null = null;

    public async register(context: FeatureContext): Promise<void> {
        this.context = context;
        if (context.debug) {
            console.log('[ProfileFeature] Registered');
        }
    }

    public async init(): Promise<void> {
        if (!this.context) return;

        if (this.context.debug) {
            console.log('[ProfileFeature] Initialized');
        }

        // Dispatch initial load event
        this.context.dispatch('athlete-dashboard')({
            type: ProfileEvent.FETCH_REQUEST,
            payload: { userId: 0 } // The actual userId will be determined by the ProfileContext
        });
    }

    public isEnabled(): boolean {
        return true;
    }

    public render(): JSX.Element | null {
        if (!this.context) {
            console.error('[ProfileFeature] Context not initialized');
            return null;
        }

        return (
            <UserProvider>
                <ProfileProvider>
                    <ProfileLayout context={this.context} />
                </ProfileProvider>
            </UserProvider>
        );
    }

    public async cleanup(): Promise<void> {
        if (this.context?.debug) {
            console.log('[ProfileFeature] Cleanup');
        }
        this.context = null;
    }

    public onNavigate(): void {
        if (this.context) {
            this.init();
        }
    }

    public onUserChange(): void {
        if (this.context) {
            this.init();
        }
    }
} 