import { FeatureInterface } from '@dashboard/core/FeatureInterface';
import { Events } from '@dashboard/events';
import { PROFILE_EVENTS } from './events';
import { profileService } from './assets/js/profileService';

export class ProfileFeature implements FeatureInterface {
    private enabled: boolean = true;

    public register(): void {
        // Register event listeners
        Events.on(PROFILE_EVENTS.LOAD, () => {
            profileService.loadProfile();
        });

        Events.on(PROFILE_EVENTS.UPDATE, (payload) => {
            profileService.updateProfile(payload.data);
        });
    }

    public init(): void {
        // Load initial profile data
        Events.emit(PROFILE_EVENTS.LOAD, null);
    }

    public getIdentifier(): string {
        return 'profile';
    }

    public getMetadata(): Record<string, unknown> {
        return {
            name: 'Profile Feature',
            description: 'Manages user profile data',
            version: '1.0.0'
        };
    }

    public isEnabled(): boolean {
        return this.enabled;
    }
} 