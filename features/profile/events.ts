/**
 * Profile Feature Events
 * Defines all events that can be emitted by the Profile feature
 */

import { Injury } from './components/InjuryTracker/types';

export const PROFILE_EVENTS = {
    PROFILE_LOADING: 'profile_loading',
    PROFILE_UPDATED: 'profile_updated',
    PROFILE_UPDATE_FAILED: 'profile_update_failed',
    INJURY_ADDED: 'profile_injury_added',
    INJURY_UPDATED: 'profile_injury_updated',
    INJURY_REMOVED: 'profile_injury_removed'
} as const;

export interface ProfileData {
    age?: number;
    gender?: string;
    height?: number;
    weight?: number;
    medicalConditions?: string;
    injuries: Injury[];
}

export interface ProfileEventPayloads {
    [PROFILE_EVENTS.PROFILE_LOADING]: undefined;
    [PROFILE_EVENTS.PROFILE_UPDATED]: ProfileData;
    [PROFILE_EVENTS.PROFILE_UPDATE_FAILED]: {
        error: string;
        profileData: ProfileData;
    };
    [PROFILE_EVENTS.INJURY_ADDED]: {
        injury: Injury;
        allInjuries: Injury[];
    };
    [PROFILE_EVENTS.INJURY_UPDATED]: {
        injury: Injury;
        allInjuries: Injury[];
    };
    [PROFILE_EVENTS.INJURY_REMOVED]: {
        injuryId: string;
        allInjuries: Injury[];
    };
} 