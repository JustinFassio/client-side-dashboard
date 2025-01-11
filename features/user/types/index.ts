import {
    _ApiResponse,
    FeatureConfig,
    User,
    _DashboardEvent,
    DashboardError,
    FeatureContext,
    Preferences,
    Permission
} from '../../../dashboard/types';

/**
 * User feature configuration
 */
export interface UserConfig extends FeatureConfig {
    roles: {
        athlete: boolean;
        coach: boolean;
        admin: boolean;
    };
    preferences: {
        defaultTheme: 'light' | 'dark' | 'system';
        defaultLanguage: string;
        notifications: {
            email: boolean;
            push: boolean;
            inApp: boolean;
        };
    };
    permissions: {
        canInviteUsers: boolean;
        canManageWorkouts: boolean;
        canViewAnalytics: boolean;
    };
}

/**
 * User settings interface
 */
export interface UserSettings {
    workoutPreferences: {
        defaultDuration: number;
        difficulty: 'beginner' | 'intermediate' | 'advanced';
        preferredWorkoutTypes: string[];
    };
    notificationSettings: {
        workoutReminders: boolean;
        progressUpdates: boolean;
        achievementAlerts: boolean;
        frequency: 'daily' | 'weekly' | 'monthly';
    };
    privacySettings: {
        profileVisibility: 'public' | 'private' | 'friends';
        activitySharing: boolean;
        showWorkoutHistory: boolean;
    };
}

/**
 * User profile interface
 */
export interface UserProfile extends User {
    bio?: string;
    avatar?: string;
    location?: string;
    socialLinks?: {
        [key: string]: string;
    };
    stats?: {
        workoutsCompleted: number;
        totalDuration: number;
        achievements: number;
    };
}

/**
 * User state interface
 */
export interface UserState {
    profile: UserProfile | null;
    settings: UserSettings | null;
    preferences: Preferences;
    permissions: Permission[];
    loading: boolean;
    error: DashboardError | null;
}

/**
 * User context interface
 */
export interface UserContext extends FeatureContext {
    state: UserState;
    updateProfile: (data: Partial<UserProfile>) => Promise<void>;
    updateSettings: (data: Partial<UserSettings>) => Promise<void>;
    updatePreferences: (data: Partial<Preferences>) => Promise<void>;
}

/**
 * User event types
 */
export enum UserEventType {
    PROFILE_UPDATE = 'USER_PROFILE_UPDATE',
    SETTINGS_UPDATE = 'USER_SETTINGS_UPDATE',
    PREFERENCES_UPDATE = 'USER_PREFERENCES_UPDATE',
    PERMISSIONS_CHANGE = 'USER_PERMISSIONS_CHANGE',
    ROLE_CHANGE = 'USER_ROLE_CHANGE'
}

/**
 * User event payloads
 */
export interface UserEventPayloads {
    [UserEventType.PROFILE_UPDATE]: Partial<UserProfile>;
    [UserEventType.SETTINGS_UPDATE]: Partial<UserSettings>;
    [UserEventType.PREFERENCES_UPDATE]: Partial<Preferences>;
    [UserEventType.PERMISSIONS_CHANGE]: Permission[];
    [UserEventType.ROLE_CHANGE]: string[];
}

/**
 * User events
 */
export type UserEvent<T extends UserEventType> = _DashboardEvent<UserEventPayloads[T]>;

/**
 * User error codes
 */
export enum UserErrorCode {
    PROFILE_UPDATE_FAILED = 'USER_PROFILE_UPDATE_FAILED',
    SETTINGS_UPDATE_FAILED = 'USER_SETTINGS_UPDATE_FAILED',
    PREFERENCES_UPDATE_FAILED = 'USER_PREFERENCES_UPDATE_FAILED',
    PERMISSION_DENIED = 'USER_PERMISSION_DENIED',
    INVALID_ROLE = 'USER_INVALID_ROLE',
    NETWORK_ERROR = 'USER_NETWORK_ERROR'
}

/**
 * User component props
 */
export interface UserSettingsProps {
    onSave: (settings: Partial<UserSettings>) => Promise<void>;
    initialSettings: UserSettings;
    className?: string;
}

export interface UserProfileProps {
    profile: UserProfile;
    editable?: boolean;
    onUpdate?: (data: Partial<UserProfile>) => Promise<void>;
    className?: string;
}

export interface UserPermissionsProps {
    userId: number;
    permissions: Permission[];
    onUpdate: (permissions: Permission[]) => Promise<void>;
    className?: string;
}

/**
 * User hook return type
 */
export interface UseUser {
    profile: UserProfile | null;
    settings: UserSettings | null;
    preferences: Preferences;
    permissions: Permission[];
    loading: boolean;
    error: DashboardError | null;
    updateProfile: (data: Partial<UserProfile>) => Promise<void>;
    updateSettings: (data: Partial<UserSettings>) => Promise<void>;
    updatePreferences: (data: Partial<Preferences>) => Promise<void>;
} 