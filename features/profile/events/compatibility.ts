import { Events } from '../../../dashboard/core/events';
import { PROFILE_EVENTS } from './constants';
import { ProfileEventType, ProfileEventPayloads } from './types';
import { ProfileEventUtils } from './utils';

/**
 * Legacy event names for backward compatibility
 */
export const LEGACY_PROFILE_EVENTS = {
    PROFILE_LOADING: 'profile_loading',
    PROFILE_UPDATED: 'profile_updated',
    PROFILE_UPDATE_FAILED: 'profile_update_failed',
    INJURY_ADDED: 'profile_injury_added',
    INJURY_UPDATED: 'profile_injury_updated',
    INJURY_REMOVED: 'profile_injury_removed'
} as const;

/**
 * Maps new event types to legacy event names
 */
const EVENT_MAPPING = {
    [PROFILE_EVENTS.FETCH_REQUEST]: LEGACY_PROFILE_EVENTS.PROFILE_LOADING,
    [PROFILE_EVENTS.UPDATE_SUCCESS]: LEGACY_PROFILE_EVENTS.PROFILE_UPDATED,
    [PROFILE_EVENTS.UPDATE_ERROR]: LEGACY_PROFILE_EVENTS.PROFILE_UPDATE_FAILED,
    [PROFILE_EVENTS.INJURY_ADDED]: LEGACY_PROFILE_EVENTS.INJURY_ADDED,
    [PROFILE_EVENTS.INJURY_UPDATED]: LEGACY_PROFILE_EVENTS.INJURY_UPDATED,
    [PROFILE_EVENTS.INJURY_REMOVED]: LEGACY_PROFILE_EVENTS.INJURY_REMOVED
} as const;

/**
 * Emits both new and legacy events for backward compatibility
 */
export function emitCompatibleEvent<T extends ProfileEventType>(
    type: T,
    payload?: ProfileEventPayloads[T]
): void {
    // Emit new event
    Events.emit(type, ProfileEventUtils.createEvent(type, payload));

    // Emit legacy event if mapping exists
    const legacyType = EVENT_MAPPING[type as keyof typeof EVENT_MAPPING];
    if (legacyType) {
        Events.emit(legacyType, payload);
    }
}

/**
 * Registers event handler for both new and legacy events
 */
export function onCompatibleEvent<T extends ProfileEventType>(
    type: T,
    handler: (payload?: ProfileEventPayloads[T]) => void
): void {
    // Listen for new event
    Events.on(type, (event) => {
        if (ProfileEventUtils.hasPayload(event)) {
            handler(event.payload);
        } else {
            handler();
        }
    });

    // Listen for legacy event if mapping exists
    const legacyType = EVENT_MAPPING[type as keyof typeof EVENT_MAPPING];
    if (legacyType) {
        Events.on(legacyType, handler);
    }
}

/**
 * Removes event handler for both new and legacy events
 */
export function offCompatibleEvent<T extends ProfileEventType>(
    type: T,
    handler: (payload?: ProfileEventPayloads[T]) => void
): void {
    // Remove new event listener
    Events.off(type, handler);

    // Remove legacy event listener if mapping exists
    const legacyType = EVENT_MAPPING[type as keyof typeof EVENT_MAPPING];
    if (legacyType) {
        Events.off(legacyType, handler);
    }
} 