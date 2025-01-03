import { ProfileData, ProfileError } from '../types/profile';
import { Injury } from '../components/InjuryTracker/types';

/**
 * Profile Event Types
 * These events are emitted during profile operations to coordinate state changes
 * and notify components of updates.
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
    | { type: typeof PROFILE_EVENTS.FORM_RESET }
    
    // Injury events
    | { type: typeof PROFILE_EVENTS.INJURY_ADDED; injury: Injury; allInjuries: Injury[] }
    | { type: typeof PROFILE_EVENTS.INJURY_UPDATED; injury: Injury; allInjuries: Injury[] }
    | { type: typeof PROFILE_EVENTS.INJURY_REMOVED; injuryId: string; allInjuries: Injury[] };

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
    // Fetch events
    [PROFILE_EVENTS.FETCH_REQUEST]: undefined;
    [PROFILE_EVENTS.FETCH_SUCCESS]: ProfileData;
    [PROFILE_EVENTS.FETCH_ERROR]: ProfileError;
    
    // Update events
    [PROFILE_EVENTS.UPDATE_REQUEST]: Partial<ProfileData>;
    [PROFILE_EVENTS.UPDATE_SUCCESS]: ProfileData;
    [PROFILE_EVENTS.UPDATE_ERROR]: ProfileError;
    
    // UI events
    [PROFILE_EVENTS.SECTION_CHANGE]: string;
    [PROFILE_EVENTS.VALIDATION_ERROR]: Record<string, string[]>;
    [PROFILE_EVENTS.FORM_RESET]: undefined;
    
    // Injury events
    [PROFILE_EVENTS.INJURY_ADDED]: { injury: Injury; allInjuries: Injury[] };
    [PROFILE_EVENTS.INJURY_UPDATED]: { injury: Injury; allInjuries: Injury[] };
    [PROFILE_EVENTS.INJURY_REMOVED]: { injuryId: string; allInjuries: Injury[] };
}; 