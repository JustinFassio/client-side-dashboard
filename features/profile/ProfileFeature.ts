import { createElement, lazy, Suspense } from '@wordpress/element';
import { Feature, FeatureContext } from '../../dashboard/contracts/Feature';
import { Events } from '../../dashboard/core/events';
import { PROFILE_EVENTS, ProfileEventPayloads } from './events';
import { ProfileService } from './services/ProfileService';
import { ProfileData } from './types/profile';
import { UserCircle2, Dumbbell, Heart, FileWarning, User } from 'lucide-react';

// Define Section interface
interface Section {
    id: string;
    title: string;
    icon: typeof UserCircle2;
    component?: React.ComponentType<any>;
}

// Define icons
const Icons = {
    UserCircle2,
    Dumbbell,
    Heart,
    FileWarning,
    User
};

// Lazy load section components
const ProfileForm = lazy(() => import('./components/form/ProfileForm').then(module => ({ default: module.ProfileForm })));
const InjuryTracker = lazy(() => import('./components/InjuryTracker').then(module => ({ default: module.InjuryTracker })));

export class ProfileFeature implements Feature {
    public readonly identifier = 'profile';
    public readonly metadata = {
        name: 'Profile',
        description: 'Manage your athlete profile',
        icon: createElement(Suspense, { fallback: null }, 
            createElement(Icons.UserCircle2, {
                size: 36,
                strokeWidth: 1.5,
                className: 'nav-feature-icon',
                color: '#ddff0e'
            })
        ),
        order: 1
    };

    private sections: Section[];

    constructor() {
        this.sections = [
            {
                id: 'basic',
                title: 'Basic Information',
                icon: Icons.UserCircle2
            },
            {
                id: 'physical',
                title: 'Physical Information',
                icon: Icons.Dumbbell
            },
            {
                id: 'medical',
                title: 'Medical Information',
                icon: Icons.Heart
            },
            {
                id: 'injuries',
                title: 'Injuries & Limitations',
                icon: Icons.FileWarning
            },
            {
                id: 'account',
                title: 'Account Settings',
                icon: Icons.User
            }
        ];
    }

    public async register(context: FeatureContext): Promise<void> {
        // Register feature with context
    }

    public async init(): Promise<void> {
        // Initialize feature
        await ProfileService.fetchProfile();
    }

    public cleanup(): void {
        // Clean up any resources
    }

    public isEnabled(): boolean {
        return true; // Profile feature is always enabled
    }

    public render(): JSX.Element {
        return createElement(Suspense, { fallback: 'Loading...' },
            createElement(ProfileForm, {
                sections: this.sections,
                onSave: this.handleProfileSave
            })
        );
    }

    private handleProfileSave = async (data: Partial<ProfileData>): Promise<void> => {
        try {
            Events.emit(PROFILE_EVENTS.FETCH_REQUEST, undefined);
            const updatedProfile = await ProfileService.updateProfile(data);
            Events.emit(PROFILE_EVENTS.UPDATE_SUCCESS, updatedProfile);
        } catch (error) {
            console.error('Failed to save profile:', error);
            Events.emit(PROFILE_EVENTS.UPDATE_ERROR, error);
        }
    };
} 