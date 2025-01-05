import React from 'react';
import { Feature, FeatureContext, FeatureMetadata, FeatureRenderProps } from '../../dashboard/contracts/Feature';
import { ProfileEvent } from './events';
import { ProfileData } from './types/profile';
import { ProfileProvider } from './context/ProfileContext';
import { ProfileLayout } from './components/layout';
import { ApiClient } from '../../dashboard/services/api';
import { API_ROUTES } from '../../dashboard/constants/api';
import { UserData } from '../../dashboard/types/api';

/**
 * ProfileFeature implements the athlete profile management functionality.
 * Responsible for:
 * - Profile data fetching and state management
 * - User profile updates and validation
 * - Profile UI rendering with ProfileProvider and ProfileLayout
 * - Handling navigation and user change events
 * 
 * @implements {Feature}
 * @see ProfileProvider
 * @see ProfileLayout
 */
export class ProfileFeature implements Feature {
    public readonly identifier = 'profile';
    public readonly metadata: FeatureMetadata = {
        name: 'Profile',
        description: 'Personalize your journey',
        order: 1
    };

    private context: FeatureContext | null = null;
    private profile: ProfileData | null = null;

    public async register(context: FeatureContext): Promise<void> {
        this.context = context;
        if (context.debug) {
            console.log('Profile feature registered');
        }
    }

    public async init(): Promise<void> {
        if (!this.context) return;

        if (this.context.debug) {
            console.log('Profile feature initialized');
        }

        const api = ApiClient.getInstance(this.context);
        const { data, error } = await api.fetchWithCache<UserData>(API_ROUTES.PROFILE);

        if (error) {
            this.context.dispatch('athlete-dashboard')({
                type: ProfileEvent.FETCH_ERROR,
                payload: { error: error.message }
            });
            return;
        }

        this.context.dispatch('athlete-dashboard')({
            type: ProfileEvent.FETCH_SUCCESS,
            payload: data
        });
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

    public async cleanup(): Promise<void> {
        this.context = null;
        this.profile = null;
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