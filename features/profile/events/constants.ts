/**
 * Profile Event Constants
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
    FORM_RESET: 'profile:form-reset',

    // Injury tracking events
    INJURY_ADDED: 'profile:injury-added',
    INJURY_UPDATED: 'profile:injury-updated',
    INJURY_REMOVED: 'profile:injury-removed'
} as const; 