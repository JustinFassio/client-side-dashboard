import { createElement } from '@wordpress/element';
import { Feature, FeatureContext } from '../../dashboard/contracts/Feature';
import { Events } from '../../dashboard/core/events';
import { ProfileForm } from './components/ProfileForm';
import { PROFILE_EVENTS, ProfileData, ProfileEventPayloads } from './events';
import { profileService } from './assets/js/profileService';

export class ProfileFeature implements Feature {
    public readonly identifier = 'profile';
    public readonly metadata = {
        name: 'Profile',
        description: 'Manage your athlete profile',
        icon: '👤',
        order: 1
    };

    private isInitialized = false;
    private context: FeatureContext | null = null;

    async register(context: FeatureContext): Promise<void> {
        this.context = context;
        // Configure profile service with context
        profileService.configure({
            nonce: context.nonce,
            apiUrl: context.apiUrl
        });
    }

    async init(): Promise<void> {
        if (this.isInitialized) {
            return;
        }

        // Register event handlers
        Events.on<ProfileEventPayloads[typeof PROFILE_EVENTS.PROFILE_UPDATED]>(
            PROFILE_EVENTS.PROFILE_UPDATED,
            this.handleProfileUpdated
        );
        Events.on<ProfileEventPayloads[typeof PROFILE_EVENTS.PROFILE_UPDATE_FAILED]>(
            PROFILE_EVENTS.PROFILE_UPDATE_FAILED,
            this.handleProfileUpdateFailed
        );

        this.isInitialized = true;
    }

    cleanup(): void {
        // Unregister event handlers
        Events.off(PROFILE_EVENTS.PROFILE_UPDATED, this.handleProfileUpdated);
        Events.off(PROFILE_EVENTS.PROFILE_UPDATE_FAILED, this.handleProfileUpdateFailed);

        this.isInitialized = false;
        this.context = null;
    }

    isEnabled(): boolean {
        return true; // Profile feature is always enabled
    }

    render(): JSX.Element {
        return createElement(ProfileForm, {
            onSave: this.handleProfileSave,
            onError: this.handleError
        });
    }

    onNavigate(): void {
        // Refresh profile data when navigating to this feature
        if (this.isInitialized) {
            Events.emit(PROFILE_EVENTS.PROFILE_LOADING, undefined);
        }
    }

    onUserChange(userId: number): void {
        // Refresh profile data when user changes
        if (this.isInitialized) {
            Events.emit(PROFILE_EVENTS.PROFILE_LOADING, undefined);
        }
    }

    private handleProfileSave = async (data: ProfileData) => {
        Events.emit(PROFILE_EVENTS.PROFILE_LOADING, undefined);
        
        try {
            const updatedProfile = await profileService.updateProfile(data);
            Events.emit(PROFILE_EVENTS.PROFILE_UPDATED, updatedProfile);
        } catch (error) {
            Events.emit(PROFILE_EVENTS.PROFILE_UPDATE_FAILED, {
                error: 'Failed to update profile',
                profileData: data
            });
        }
    };

    private handleProfileUpdated = (payload: ProfileEventPayloads[typeof PROFILE_EVENTS.PROFILE_UPDATED]) => {
        // Handle successful profile update
        // This could trigger notifications or other UI updates
        console.log('Profile updated successfully:', payload);
    };

    private handleProfileUpdateFailed = (payload: ProfileEventPayloads[typeof PROFILE_EVENTS.PROFILE_UPDATE_FAILED]) => {
        // Handle failed profile update
        console.error('Profile update failed:', payload.error);
    };

    private handleError = (error: string) => {
        Events.emit(PROFILE_EVENTS.PROFILE_UPDATE_FAILED, { error });
    };
} 