/**
 * Profile Events System
 * Exports a unified API for the profile feature's event system
 */

import { ProfileData } from '../types/profile';
import { DashboardEvents } from '../../../dashboard/core/events';

export enum ProfileEvent {
    FETCH_REQUEST = 'profile:fetch-request',
    FETCH_SUCCESS = 'profile:fetch-success',
    FETCH_ERROR = 'profile:fetch-error',
    UPDATE_REQUEST = 'profile:update-request',
    UPDATE_SUCCESS = 'profile:update-success',
    UPDATE_ERROR = 'profile:update-error',
    SECTION_CHANGE = 'profile:section-change'
}

export interface ProfileEventPayloads {
    [ProfileEvent.FETCH_REQUEST]: {
        userId: number;
    };
    [ProfileEvent.FETCH_SUCCESS]: {
        profile: ProfileData;
    };
    [ProfileEvent.FETCH_ERROR]: {
        error: Error;
    };
    [ProfileEvent.UPDATE_REQUEST]: {
        userId: number;
        data: Partial<ProfileData>;
    };
    [ProfileEvent.UPDATE_SUCCESS]: {
        profile: ProfileData;
    };
    [ProfileEvent.UPDATE_ERROR]: {
        error: Error;
    };
    [ProfileEvent.SECTION_CHANGE]: {
        section: string;
    };
}

export type ProfileEventType = ProfileEvent;

export const PROFILE_EVENTS = ProfileEvent;

export const emitProfileEvent = <T extends ProfileEventType>(
    events: DashboardEvents,
    type: T,
    payload: ProfileEventPayloads[T]
): void => {
    events.emit(type, payload);
};

export const onProfileEvent = <T extends ProfileEventType>(
    events: DashboardEvents,
    type: T,
    handler: (payload: ProfileEventPayloads[T]) => void
): void => {
    events.on(type, handler);
};

export const offProfileEvent = <T extends ProfileEventType>(
    events: DashboardEvents,
    type: T,
    handler: (payload: ProfileEventPayloads[T]) => void
): void => {
    events.off(type, handler);
};

export {
    LEGACY_PROFILE_EVENTS
} from './compatibility'; 