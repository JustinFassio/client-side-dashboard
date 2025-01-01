import { ProfileConfig } from '../config';
import { ProfileError } from '../services/ProfileService';
import type { ProfileSuccessEvent, ProfileUpdatedEvent, ProfileErrorEvent } from '../types/events';

export class ProfileEvents {
    /**
     * Emit success event with messages
     */
    static emitSuccess(messages: string[]): void {
        window.dispatchEvent(new CustomEvent<ProfileSuccessEvent>(
            ProfileConfig.events.SUCCESS,
            { detail: { messages } }
        ));
    }

    /**
     * Emit profile updated event
     */
    static emitUpdated(profile: any): void {
        window.dispatchEvent(new CustomEvent<ProfileUpdatedEvent>(
            ProfileConfig.events.UPDATED,
            { detail: { profile } }
        ));
    }

    /**
     * Emit error event
     */
    static emitError(error: ProfileError): void {
        window.dispatchEvent(new CustomEvent<ProfileErrorEvent>(
            ProfileConfig.events.ERROR,
            { detail: { error } }
        ));
    }

    /**
     * Add success event listener
     */
    static onSuccess(callback: (event: CustomEvent<ProfileSuccessEvent>) => void): () => void {
        const handler = (event: Event) => callback(event as CustomEvent<ProfileSuccessEvent>);
        window.addEventListener(ProfileConfig.events.SUCCESS, handler);
        return () => window.removeEventListener(ProfileConfig.events.SUCCESS, handler);
    }

    /**
     * Add profile updated event listener
     */
    static onUpdated(callback: (event: CustomEvent<ProfileUpdatedEvent>) => void): () => void {
        const handler = (event: Event) => callback(event as CustomEvent<ProfileUpdatedEvent>);
        window.addEventListener(ProfileConfig.events.UPDATED, handler);
        return () => window.removeEventListener(ProfileConfig.events.UPDATED, handler);
    }

    /**
     * Add error event listener
     */
    static onError(callback: (event: CustomEvent<ProfileErrorEvent>) => void): () => void {
        const handler = (event: Event) => callback(event as CustomEvent<ProfileErrorEvent>);
        window.addEventListener(ProfileConfig.events.ERROR, handler);
        return () => window.removeEventListener(ProfileConfig.events.ERROR, handler);
    }
} 