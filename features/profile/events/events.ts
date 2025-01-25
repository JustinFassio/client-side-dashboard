import { ProfileData, ProfileError } from '../types/profile';

/**
 * Profile Event Types
 * These events are emitted during profile operations to coordinate state changes
 * and notify components of updates.
 */
export const PROFILE_EVENTS = {
    // Data fetching events
    FETCH_REQUEST: 'profile:fetch-request',
    FETCH_SUCCESS: 'profile:fetch-success',
    FETCH_ERROR: 'profile:fetch-error',

    // Update events
    UPDATE_REQUEST: 'profile:update-request',
    UPDATE_SUCCESS: 'profile:update-success',
    UPDATE_ERROR: 'profile:update-error',

    // UI events
    SECTION_CHANGE: 'profile:section-change',
    VALIDATION_ERROR: 'profile:validation-error',
    FORM_RESET: 'profile:form-reset'
} as const;

/**
 * Profile Event Type
 * Union type of all possible profile events
 */
export type ProfileEventType = typeof PROFILE_EVENTS[keyof typeof PROFILE_EVENTS];

/**
 * Profile Event Interface
 * Discriminated union of all possible profile event payloads
 */
export type ProfileEvent = 
    // Fetch events
    | { type: typeof PROFILE_EVENTS.FETCH_REQUEST }
    | { type: typeof PROFILE_EVENTS.FETCH_SUCCESS; payload: ProfileData }
    | { type: typeof PROFILE_EVENTS.FETCH_ERROR; error: ProfileError }
    
    // Update events
    | { type: typeof PROFILE_EVENTS.UPDATE_REQUEST; payload: Partial<ProfileData> }
    | { type: typeof PROFILE_EVENTS.UPDATE_SUCCESS; payload: ProfileData }
    | { type: typeof PROFILE_EVENTS.UPDATE_ERROR; error: ProfileError }
    
    // UI events
    | { type: typeof PROFILE_EVENTS.SECTION_CHANGE; section: string }
    | { type: typeof PROFILE_EVENTS.VALIDATION_ERROR; errors: Record<string, string[]> }
    | { type: typeof PROFILE_EVENTS.FORM_RESET };

/**
 * Profile Event Handler Type
 * Type for event handlers that handle specific profile events
 */
export type ProfileEventHandler<T extends ProfileEvent> = (event: T) => void;

/**
 * Profile Event Payloads
 * Mapping of event types to their payload types
 */
export type ProfileEventPayloads = {
    [PROFILE_EVENTS.FETCH_REQUEST]: undefined;
    [PROFILE_EVENTS.FETCH_SUCCESS]: ProfileData;
    [PROFILE_EVENTS.FETCH_ERROR]: ProfileError;
    [PROFILE_EVENTS.UPDATE_REQUEST]: Partial<ProfileData>;
    [PROFILE_EVENTS.UPDATE_SUCCESS]: ProfileData;
    [PROFILE_EVENTS.UPDATE_ERROR]: ProfileError;
    [PROFILE_EVENTS.SECTION_CHANGE]: string;
    [PROFILE_EVENTS.VALIDATION_ERROR]: Record<string, string[]>;
    [PROFILE_EVENTS.FORM_RESET]: undefined;
};

/**
 * Event Utilities
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
    }
}; 