import { createElement, lazy, Suspense } from '@wordpress/element';
import { Feature, FeatureContext } from '../../dashboard/contracts/Feature';
import { Events } from '../../dashboard/core/events';
import { PROFILE_EVENTS, ProfileEventPayloads } from './events';
import { ProfileService } from './services/ProfileService';
import { ProfileData } from './types/profile';
import { LucideIcon } from 'lucide-react';

// Define Section interface
interface Section {
    id: string;
    title: string;
    icon: LucideIcon;
    component?: React.ComponentType<any>;
}

// Lazy load icons and cast them to LucideIcon type
const Icons = {
    UserCircle2: lazy(() => import('lucide-react').then(mod => ({ default: mod.UserCircle2 }))) as unknown as LucideIcon,
    Dumbbell: lazy(() => import('lucide-react').then(mod => ({ default: mod.Dumbbell }))) as unknown as LucideIcon,
    Heart: lazy(() => import('lucide-react').then(mod => ({ default: mod.Heart }))) as unknown as LucideIcon,
    FileWarning: lazy(() => import('lucide-react').then(mod => ({ default: mod.FileWarning }))) as unknown as LucideIcon,
    User: lazy(() => import('lucide-react').then(mod => ({ default: mod.User }))) as unknown as LucideIcon
};

// Lazy load section components
const ProfileForm = lazy(() => import('./components/ProfileForm').then(module => ({ default: module.ProfileForm })));
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
            Events.emit(PROFILE_EVENTS.PROFILE_LOADING, undefined);
            const updatedProfile = await ProfileService.updateProfile(data);
            Events.emit(PROFILE_EVENTS.PROFILE_UPDATED, updatedProfile);
        } catch (error) {
            console.error('Failed to save profile:', error);
            Events.emit(PROFILE_EVENTS.PROFILE_UPDATE_FAILED, undefined);
        }
    };
} 