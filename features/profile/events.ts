/**
 * Profile Feature Events
 * Defines all events that can be emitted by the Profile feature
 */

export const PROFILE_EVENTS = {
    PROFILE_LOADED: 'profile:loaded',
    PROFILE_UPDATED: 'profile:updated',
    PROFILE_UPDATE_FAILED: 'profile:update_failed',
    PROFILE_LOADING: 'profile:loading',
} as const;

export enum Gender {
    MALE = 'male',
    FEMALE = 'female',
    OTHER = 'other',
    PREFER_NOT_TO_SAY = 'prefer_not_to_say'
}

export interface MedicalInfo {
    hasInjuries: boolean;
    injuries?: string;
    hasMedicalClearance: boolean;
    medicalClearanceDate?: string;
    medicalNotes?: string;
}

export interface ProfileData {
    // Basic Info
    firstName: string;
    lastName: string;
    email: string;
    
    // Physical Attributes
    age: number;
    gender: Gender;
    height: number; // in centimeters
    weight: number; // in kilograms
    
    // Medical Information
    medicalInfo: MedicalInfo;
    
    // Optional Information
    bio?: string;
    fitnessGoals?: string;
    preferredWorkoutTypes?: string[];
}

export type ProfileEventPayloads = {
    [PROFILE_EVENTS.PROFILE_LOADED]: ProfileData;
    [PROFILE_EVENTS.PROFILE_UPDATED]: ProfileData;
    [PROFILE_EVENTS.PROFILE_UPDATE_FAILED]: {
        error: string;
        profileData?: Partial<ProfileData>;
    };
    [PROFILE_EVENTS.PROFILE_LOADING]: undefined;
};

// Type guard for profile data
export function isProfileData(data: unknown): data is ProfileData {
    if (!data || typeof data !== 'object') return false;
    
    const profile = data as Partial<ProfileData>;
    return (
        typeof profile.firstName === 'string' &&
        typeof profile.lastName === 'string' &&
        typeof profile.email === 'string' &&
        typeof profile.age === 'number' &&
        typeof profile.gender === 'string' &&
        typeof profile.height === 'number' &&
        typeof profile.weight === 'number' &&
        typeof profile.medicalInfo === 'object'
    );
} 