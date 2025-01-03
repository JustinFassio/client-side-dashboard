import { ProfileEvent, ProfileEventType, ProfileEventPayloads } from './types';
import { PROFILE_EVENTS } from './constants';

/**
 * Event Utilities for Profile Feature
 */
export const ProfileEventUtils = {
    /**
     * Creates a strongly typed event with payload
     */
    createEvent<T extends ProfileEventType>(
        type: T,
        payload?: ProfileEventPayloads[T]
    ): { type: T; payload?: ProfileEventPayloads[T] } {
        return { type, payload };
    },

    /**
     * Type guard to check if an event is a specific type
     */
    isEventType<T extends ProfileEventType>(
        event: ProfileEvent,
        type: T
    ): event is Extract<ProfileEvent, { type: T }> {
        return event.type === type;
    },

    /**
     * Type guard to check if an event has a payload
     */
    hasPayload<T extends ProfileEventType>(
        event: ProfileEvent & { type: T }
    ): event is Extract<ProfileEvent, { type: T; payload: any }> {
        return 'payload' in event;
    },

    /**
     * Logs event for debugging purposes
     */
    debugEvent<T extends ProfileEventType>(
        type: T,
        payload?: ProfileEventPayloads[T]
    ): void {
        if (process.env.NODE_ENV === 'development') {
            console.group(`Profile Event: ${type}`);
            if (payload) console.log('Payload:', payload);
            console.groupEnd();
        }
    }
}; 