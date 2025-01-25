import { ProfileData, ProfileError } from '../types/profile';

export enum ProfileEvent {
    FETCH_REQUEST = 'profile:fetch-request',
    FETCH_SUCCESS = 'profile:fetch-success',
    FETCH_ERROR = 'profile:fetch-error',
    UPDATE_REQUEST = 'profile:update-request',
    UPDATE_SUCCESS = 'profile:update-success',
    UPDATE_ERROR = 'profile:update-error',
    SECTION_CHANGE = 'profile:section-change',
    VALIDATION_ERROR = 'profile:validation-error',
    FORM_RESET = 'profile:form-reset',
    PREFERENCES_UPDATED = 'profile:preferences-updated'
}

export interface ProfileEventPayloads {
    [ProfileEvent.FETCH_REQUEST]: { userId: number };
    [ProfileEvent.FETCH_SUCCESS]: ProfileData;
    [ProfileEvent.FETCH_ERROR]: { error: ProfileError };
    [ProfileEvent.UPDATE_REQUEST]: { data: Partial<ProfileData> };
    [ProfileEvent.UPDATE_SUCCESS]: ProfileData;
    [ProfileEvent.UPDATE_ERROR]: { error: ProfileError };
    [ProfileEvent.SECTION_CHANGE]: { section: string };
    [ProfileEvent.VALIDATION_ERROR]: { error: ProfileError };
    [ProfileEvent.FORM_RESET]: null;
    [ProfileEvent.PREFERENCES_UPDATED]: ProfileData;
} 