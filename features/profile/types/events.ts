import { ProfileData, ProfileError } from './profile';

export const PROFILE_EVENTS = {
    FETCH_REQUEST: 'profile:fetch-request',
    FETCH_SUCCESS: 'profile:fetch-success',
    FETCH_ERROR: 'profile:fetch-error',
    UPDATE_REQUEST: 'profile:update-request',
    UPDATE_SUCCESS: 'profile:update-success',
    UPDATE_ERROR: 'profile:update-error',
    SECTION_CHANGE: 'profile:section-change'
} as const;

export type ProfileEventType = typeof PROFILE_EVENTS[keyof typeof PROFILE_EVENTS];

export type ProfileEvent = 
  | { type: typeof PROFILE_EVENTS.FETCH_REQUEST }
  | { type: typeof PROFILE_EVENTS.FETCH_SUCCESS; payload: ProfileData }
  | { type: typeof PROFILE_EVENTS.FETCH_ERROR; error: ProfileError }
  | { type: typeof PROFILE_EVENTS.UPDATE_REQUEST; payload: Partial<ProfileData> }
  | { type: typeof PROFILE_EVENTS.UPDATE_SUCCESS; payload: ProfileData }
  | { type: typeof PROFILE_EVENTS.UPDATE_ERROR; error: ProfileError }
  | { type: typeof PROFILE_EVENTS.SECTION_CHANGE; section: string };

export type ProfileEventHandler<T extends ProfileEvent> = (event: T) => void;

export type ProfileEventPayloads = {
    [PROFILE_EVENTS.FETCH_REQUEST]: undefined;
    [PROFILE_EVENTS.FETCH_SUCCESS]: ProfileData;
    [PROFILE_EVENTS.FETCH_ERROR]: ProfileError;
    [PROFILE_EVENTS.UPDATE_REQUEST]: Partial<ProfileData>;
    [PROFILE_EVENTS.UPDATE_SUCCESS]: ProfileData;
    [PROFILE_EVENTS.UPDATE_ERROR]: ProfileError;
    [PROFILE_EVENTS.SECTION_CHANGE]: string;
}; 