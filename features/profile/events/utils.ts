import { ProfileEvent, ProfileEventPayloads } from './types';

export const PROFILE_EVENT_TYPES = {
    FETCH_REQUEST: 'profile:fetch-request',
    FETCH_SUCCESS: 'profile:fetch-success',
    FETCH_ERROR: 'profile:fetch-error',
    UPDATE_REQUEST: 'profile:update-request',
    UPDATE_SUCCESS: 'profile:update-success',
    UPDATE_ERROR: 'profile:update-error',
    SECTION_CHANGE: 'profile:section-change'
} as const;

export type ProfileEventType = typeof PROFILE_EVENT_TYPES[keyof typeof PROFILE_EVENT_TYPES];

export function createProfileEvent<T extends keyof typeof PROFILE_EVENT_TYPES>(
    event: T,
    payload: ProfileEventPayloads[typeof PROFILE_EVENT_TYPES[T]]
): { type: typeof PROFILE_EVENT_TYPES[T]; payload: ProfileEventPayloads[typeof PROFILE_EVENT_TYPES[T]] } {
    return { type: PROFILE_EVENT_TYPES[event], payload };
}

export function isProfileEvent(event: any): event is { type: ProfileEventType } {
    return event && typeof event === 'object' && 'type' in event && 
        Object.values(PROFILE_EVENT_TYPES).includes(event.type);
}

export function getEventType(event: { type: ProfileEventType }): ProfileEventType {
    return event.type;
}

export function createEventHandler<T extends keyof typeof PROFILE_EVENT_TYPES>(
    event: T,
    handler: (payload: ProfileEventPayloads[typeof PROFILE_EVENT_TYPES[T]]) => void
): (event: { type: typeof PROFILE_EVENT_TYPES[T]; payload: ProfileEventPayloads[typeof PROFILE_EVENT_TYPES[T]] }) => void {
    return (e) => handler(e.payload);
} 