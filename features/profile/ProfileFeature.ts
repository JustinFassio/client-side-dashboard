import { createElement } from '@wordpress/element';
import { Feature, FeatureContext } from '../../dashboard/contracts/Feature';
import { Events } from '../../dashboard/core/events';
import { UserCircle2, Dumbbell, Heart, FileWarning } from 'lucide-react';
import { PROFILE_EVENTS, ProfileData, ProfileEventPayloads } from './events';
import { ProfileForm } from './components/ProfileForm';
import { profileService } from './assets/js/profileService';

export class ProfileFeature implements Feature {
    public readonly identifier = 'profile';
    public readonly metadata = {
        name: 'Profile',
        description: 'Manage your athlete profile',
        icon: createElement(UserCircle2, {
            size: 36,
            strokeWidth: 1.5,
            className: 'nav-feature-icon',
            color: '#ddff0e'
        }),
        order: 1
    };

    private isInitialized = false;
    private context: FeatureContext | null = null;

    sections = [
        {
            id: 'basic',
            title: 'Basic Information',
            icon: UserCircle2,
        },
        {
            id: 'physical',
            title: 'Physical Information',
            icon: Dumbbell,
        },
        {
            id: 'medical',
            title: 'Medical Information',
            icon: Heart,
        },
        {
            id: 'injuries',
            title: 'Injuries & Limitations',
            icon: FileWarning,
        }
    ];

    async register(context: FeatureContext): Promise<void> {
        this.context = context;
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
            sections: this.sections
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
} 