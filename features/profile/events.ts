export interface ProfileData {
    id: number;
    name: string;
    email: string;
    age?: number;
    gender?: 'male' | 'female' | 'other' | '';
    height?: number;  // in cm
    weight?: number;  // in kg
    injuries?: string[];
    medicalClearance?: boolean;
}

export interface ProfileUpdatePayload {
    data: Partial<ProfileData>;
    error?: string;
}

export const PROFILE_EVENTS = {
    UPDATED: 'profile:updated',
    LOAD: 'profile:load',
    LOADED: 'profile:loaded',
    UPDATE: 'profile:update',
    ERROR: 'profile:error'
} as const;

export type ProfileEventTypes = typeof PROFILE_EVENTS[keyof typeof PROFILE_EVENTS]; 