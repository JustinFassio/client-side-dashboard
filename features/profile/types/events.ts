import { ProfileData, ProfileError } from '../services/ProfileService';

export interface ProfileSuccessEvent {
    messages: string[];
}

export interface ProfileUpdatedEvent {
    profile: ProfileData;
}

export interface ProfileErrorEvent {
    error: ProfileError;
}

// Event name constants to ensure consistency
export const PROFILE_EVENTS = {
    SUCCESS: 'profile:success',
    UPDATED: 'profile:updated',
    ERROR: 'profile:error'
} as const; 