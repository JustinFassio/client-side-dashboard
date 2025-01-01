import { createElement } from '@wordpress/element';
import { Feature, FeatureContext } from '../../dashboard/contracts/Feature';
import { Events } from '../../dashboard/core/events';
import { UserCircle2, Dumbbell, Heart, FileWarning, User } from 'lucide-react';
import { ProfileData } from './types/profile';
import { PROFILE_EVENTS, ProfileEventPayloads } from './events';
import { ProfileService } from './services/ProfileService';
import { ProfileForm } from './components/ProfileForm';
import { InjuryTracker } from './components/InjuryTracker';

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
            component: InjuryTracker
        },
        {
            id: 'account',
            title: 'Account Info',
            icon: User,
        }
    ];

    async register(context: FeatureContext): Promise<void> {
        this.context = context;
    }

    async init(): Promise<void> {
        if (this.isInitialized) {
            return;
        }

        // Load initial profile data
        await this.loadProfileData();

        // Register event handlers
        Events.on<ProfileEventPayloads[typeof PROFILE_EVENTS.PROFILE_UPDATED]>(
            PROFILE_EVENTS.PROFILE_UPDATED,
            this.handleProfileUpdated
        );
        Events.on<ProfileEventPayloads[typeof PROFILE_EVENTS.PROFILE_UPDATE_FAILED]>(
            PROFILE_EVENTS.PROFILE_UPDATE_FAILED,
            this.handleProfileUpdateFailed
        );
        Events.on<ProfileEventPayloads[typeof PROFILE_EVENTS.INJURY_ADDED]>(
            PROFILE_EVENTS.INJURY_ADDED,
            this.handleInjuryAdded
        );
        Events.on<ProfileEventPayloads[typeof PROFILE_EVENTS.INJURY_UPDATED]>(
            PROFILE_EVENTS.INJURY_UPDATED,
            this.handleInjuryUpdated
        );
        Events.on<ProfileEventPayloads[typeof PROFILE_EVENTS.INJURY_REMOVED]>(
            PROFILE_EVENTS.INJURY_REMOVED,
            this.handleInjuryRemoved
        );

        this.isInitialized = true;
    }

    cleanup(): void {
        // Unregister event handlers
        Events.off(PROFILE_EVENTS.PROFILE_UPDATED, this.handleProfileUpdated);
        Events.off(PROFILE_EVENTS.PROFILE_UPDATE_FAILED, this.handleProfileUpdateFailed);
        Events.off(PROFILE_EVENTS.INJURY_ADDED, this.handleInjuryAdded);
        Events.off(PROFILE_EVENTS.INJURY_UPDATED, this.handleInjuryUpdated);
        Events.off(PROFILE_EVENTS.INJURY_REMOVED, this.handleInjuryRemoved);

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
            this.loadProfileData();
        }
    }

    onUserChange(userId: number): void {
        // Refresh profile data when user changes
        if (this.isInitialized) {
            this.loadProfileData();
        }
    }

    private async loadProfileData(): Promise<void> {
        Events.emit(PROFILE_EVENTS.PROFILE_LOADING, undefined);
        try {
            const profile = await ProfileService.fetchProfile();
            Events.emit(PROFILE_EVENTS.PROFILE_UPDATED, profile);
        } catch (error) {
            Events.emit(PROFILE_EVENTS.PROFILE_UPDATE_FAILED, {
                error: 'Failed to load profile',
                profileData: ProfileService.getDefaultProfile()
            });
        }
    }

    private handleProfileSave = async (data: Partial<ProfileData>): Promise<void> => {
        Events.emit(PROFILE_EVENTS.PROFILE_LOADING, undefined);
        
        try {
            const updatedProfile = await ProfileService.updateProfile(data);
            Events.emit(PROFILE_EVENTS.PROFILE_UPDATED, updatedProfile);
        } catch (error) {
            Events.emit(PROFILE_EVENTS.PROFILE_UPDATE_FAILED, {
                error: 'Failed to update profile',
                profileData: data
            });
        }
    };

    private handleProfileUpdated = (payload: ProfileEventPayloads[typeof PROFILE_EVENTS.PROFILE_UPDATED]) => {
        console.log('Profile updated successfully:', payload);
    };

    private handleProfileUpdateFailed = (payload: ProfileEventPayloads[typeof PROFILE_EVENTS.PROFILE_UPDATE_FAILED]) => {
        console.error('Profile update failed:', payload.error);
    };

    private handleInjuryAdded = (payload: ProfileEventPayloads[typeof PROFILE_EVENTS.INJURY_ADDED]) => {
        console.log('Injury added:', payload.injury);
    };

    private handleInjuryUpdated = (payload: ProfileEventPayloads[typeof PROFILE_EVENTS.INJURY_UPDATED]) => {
        console.log('Injury updated:', payload.injury);
    };

    private handleInjuryRemoved = (payload: ProfileEventPayloads[typeof PROFILE_EVENTS.INJURY_REMOVED]) => {
        console.log('Injury removed:', payload.injuryId);
    };
} 