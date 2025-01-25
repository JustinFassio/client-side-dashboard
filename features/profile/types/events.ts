import { ProfileData } from './profile';

/**
 * Legacy profile events for backward compatibility
 */
export const LEGACY_PROFILE_EVENTS = {
    PROFILE_UPDATED: 'profile_updated',
    PROFILE_LOADED: 'profile_loaded',
    PROFILE_ERROR: 'profile_error',
    PROFILE_DELETED: 'profile_deleted'
} as const;

export type LegacyProfileEvent = typeof LEGACY_PROFILE_EVENTS[keyof typeof LEGACY_PROFILE_EVENTS];

/**
 * Modern profile events
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

export type ProfileEventType = typeof PROFILE_EVENTS[keyof typeof PROFILE_EVENTS];

/**
 * Event payload types
 */
export type ProfileEventPayloads = {
    [PROFILE_EVENTS.FETCH_REQUEST]: {
        userId: number;
    };
    [PROFILE_EVENTS.FETCH_SUCCESS]: {
        profile: ProfileData;
    };
    [PROFILE_EVENTS.FETCH_ERROR]: {
        error: Error;
    };
    [PROFILE_EVENTS.UPDATE_REQUEST]: {
        userId: number;
        data: Partial<ProfileData>;
    };
    [PROFILE_EVENTS.UPDATE_SUCCESS]: {
        profile: ProfileData;
        updatedFields?: Array<keyof ProfileData>;
    };
    [PROFILE_EVENTS.UPDATE_ERROR]: {
        error: Error;
        attemptedData?: Partial<ProfileData>;
    };
    [PROFILE_EVENTS.SECTION_CHANGE]: {
        from: string;
        to: string;
    };
    [PROFILE_EVENTS.VALIDATION_ERROR]: {
        errors: Record<string, string[]>;
    };
    [PROFILE_EVENTS.FORM_RESET]: undefined;
}; 