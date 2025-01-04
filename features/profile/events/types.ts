import { ProfileData } from '../types/profile';

export const ProfileEvent = {
    FETCH_REQUEST: 'profile:fetch-request',
    FETCH_SUCCESS: 'profile:fetch-success',
    FETCH_ERROR: 'profile:fetch-error',
    UPDATE_REQUEST: 'profile:update-request',
    UPDATE_SUCCESS: 'profile:update-success',
    UPDATE_ERROR: 'profile:update-error',
    SECTION_CHANGE: 'profile:section-change'
} as const;

export type ProfileEventType = typeof ProfileEvent[keyof typeof ProfileEvent];

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
        updatedFields?: Array<keyof ProfileData>;
    };
    [ProfileEvent.UPDATE_ERROR]: {
        error: Error;
        attemptedData?: Partial<ProfileData>;
    };
    [ProfileEvent.SECTION_CHANGE]: {
        from: string;
        to: string;
    };
} 