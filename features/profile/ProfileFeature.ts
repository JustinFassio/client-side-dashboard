import React from 'react';
import { Feature, FeatureContext, FeatureMetadata, FeatureRenderProps } from '../../dashboard/contracts/Feature';
import { ProfileEvent } from './events';
import { ProfileData } from './types/profile';
import { ProfileProvider } from './context/ProfileContext';
import { ProfileLayout } from './components/layout';
import { ApiClient } from '../../dashboard/services/api';
import { API_ROUTES } from '../../dashboard/constants/api';
import { UserData } from '../../dashboard/types/api';

export class ProfileFeature implements Feature {
    public readonly identifier = 'profile';
    public readonly metadata: FeatureMetadata = {
        name: 'Profile',
        description: 'Manage your athlete profile'
    };

    private context: FeatureContext | null = null;
    private profile: ProfileData | null = null;

    public async register(context: FeatureContext): Promise<void> {
        this.context = context;
    }

    public async init(): Promise<void> {
        if (!this.context) return;

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
            return null;
        }

        return React.createElement(ProfileProvider, { 
            userId,
            children: React.createElement(ProfileLayout, {
                userId,
                context: this.context
            })
        });
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