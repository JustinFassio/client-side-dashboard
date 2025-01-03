/**
 * Profile Event Constants
 * These events are emitted during profile operations to coordinate state changes
 * and notify components of updates.
 */
export const PROFILE_EVENTS = {
    // Data fetching events
    FETCH_REQUEST: 'profile_fetch_request',
    FETCH_SUCCESS: 'profile_fetch_success',
    FETCH_ERROR: 'profile_fetch_error',

    // Update events
    UPDATE_REQUEST: 'profile_update_request',
    UPDATE_SUCCESS: 'profile_update_success',
    UPDATE_ERROR: 'profile_update_error',

    // UI events
    SECTION_CHANGE: 'profile_section_change',
    VALIDATION_ERROR: 'profile_validation_error',
    FORM_RESET: 'profile_form_reset',

    // Injury tracking events
    INJURY_ADDED: 'profile_injury_added',
    INJURY_UPDATED: 'profile_injury_updated',
    INJURY_REMOVED: 'profile_injury_removed'
} as const; 