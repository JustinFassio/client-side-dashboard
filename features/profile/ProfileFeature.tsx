import React from 'react';
import { Feature, FeatureContext, FeatureMetadata, FeatureRenderProps } from '../../dashboard/contracts/Feature';
import { ProfileEvent } from './events';
import { ProfileProvider, useProfile } from './context/ProfileContext';
import { ProfileLayout } from './components/layout';
import { UserProvider } from '../user/context/UserContext';
import { ProfileData, ProfileErrorCode, ProfileState, ProfileError } from './types/profile';
import { ProfileService } from './services/ProfileService';
import { ApiClient } from '../../dashboard/services/api';
import { AxiosInstance } from 'axios';

/**
 * ProfileFeature implements the athlete profile management functionality.
 * Responsible for:
 * - Profile data management through ProfileContext
 * - User authentication through UserContext
 * - Profile UI rendering with ProfileLayout
 * - Event handling and navigation
 */

interface ApiResponse<T> {
    success: boolean;
    data: T;
}

interface ProfileResponse {
    id: number;
    profile: ProfileData;
}

interface ProfileContentProps {
  api: AxiosInstance;
  userId: number;
}

const ProfileContent: React.FC = () => {
  const { loading, error, profile } = useProfile();

  if (loading) {
    return <div>Loading profile...</div>;
  }

  if (error) {
    return <div>Error: {error.message}</div>;
  }

  if (!profile) {
    return <div>No profile data found</div>;
  }

  return (
    <div>
      <h2>User Profile</h2>
      <div>
        <h3>User ID: {profile.user_id}</h3>
        <pre>{JSON.stringify(profile.data, null, 2)}</pre>
      </div>
    </div>
  );
};

export class ProfileFeature implements Feature {
    public readonly identifier = 'profile';
    public readonly metadata: FeatureMetadata = {
        name: 'Profile',
        description: 'Personalize your journey',
        order: 1
    };

    private context: FeatureContext | null = null;
    private renderProps: FeatureRenderProps | null = null;
    private state: ProfileState = {
        isComplete: false,
        isLoading: false,
        error: null,
        data: null
    };

    public getState(): ProfileState {
        return this.state;
    }

    public setState(state: Partial<ProfileState>): void {
        this.state = { ...this.state, ...state };
    }

    public async register(context: FeatureContext): Promise<void> {
        this.context = context;
        if (context.debug) {
            console.log('[ProfileFeature] Registered with context:', context);
        }
    }

    public async init(): Promise<void> {
        if (!this.context) {
            console.error('[ProfileFeature] Initialization failed: Context not set');
            throw new Error('Context not set');
        }

        try {
            this.setState({ isLoading: true, error: null });
            
            ApiClient.getInstance(this.context);
            
            if (this.context.debug) {
                console.log('[ProfileFeature] Initialized with context:', this.context);
            }
            
            this.setState({ isComplete: true });
        } catch (error) {
            console.error('[ProfileFeature] Initialization error:', error);
            this.setState({
                error: {
                    code: 'INITIALIZATION_ERROR' as ProfileErrorCode,
                    message: error instanceof Error ? error.message : 'Failed to initialize profile',
                    status: 500
                }
            });
        } finally {
            this.setState({ isLoading: false });
        }
    }

    public isEnabled(): boolean {
        return true;
    }

    public async cleanup(): Promise<void> {
        if (this.context?.debug) {
            console.log('[ProfileFeature] Cleanup');
        }
        this.context = null;
        this.renderProps = null;
        this.setState({
            isComplete: false,
            isLoading: false,
            error: null,
            data: null
        });
    }

    public render({ userId }: FeatureRenderProps): JSX.Element {
        if (!this.context) {
            console.error('[ProfileFeature] Render failed: Context not set');
            return <div>Error: Profile feature not properly initialized</div>;
        }

        if (this.state.error) {
            return <div>Error: {this.state.error.message}</div>;
        }

        const api = ApiClient.getInstance(this.context);

        return (
            <UserProvider>
                <ProfileProvider 
                    api={api} 
                    userId={userId}
                >
                    <ProfileLayout context={this.context}>
                        <ProfileContent />
                    </ProfileLayout>
                </ProfileProvider>
            </UserProvider>
        );
    }

    public async handleEvent(event: ProfileEvent): Promise<void> {
        if (!this.context) {
            throw new Error('Context not set');
        }

        try {
            this.setState({ isLoading: true, error: null });
            // Handle profile-specific events...
        } catch (error) {
            this.setState({
                error: {
                    code: 'EVENT_ERROR' as ProfileErrorCode,
                    message: error instanceof Error ? error.message : 'Failed to handle event',
                    status: 500
                }
            });
        } finally {
            this.setState({ isLoading: false });
        }
    }
} 