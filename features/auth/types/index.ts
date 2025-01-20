import {
    ApiResponse,
    FeatureConfig,
    User,
    DashboardEvent,
    DashboardError,
    FeatureContext
} from '../../../dashboard/types';

/**
 * Auth feature configuration
 */
export interface AuthConfig extends FeatureConfig {
    registration: {
        enabled: boolean;
        requireInviteCode: boolean;
    };
    security: {
        maxLoginAttempts: number;
        lockoutDuration: number;
        passwordStrength: 'weak' | 'medium' | 'strong';
    };
    redirects: {
        afterLogin: string;
        afterLogout: string;
        afterRegistration: string;
    };
}

/**
 * Login request payload
 */
export interface LoginRequest {
    username: string;
    password: string;
    rememberMe?: boolean;
}

/**
 * Login response data
 */
export type LoginResponse = ApiResponse<{
    token: string;
    user: User;
}>;

/**
 * Registration request payload
 */
export interface RegisterRequest {
    username: string;
    email: string;
    password: string;
    firstName: string;
    lastName: string;
    inviteCode?: string;
}

/**
 * Registration response data
 */
export type RegisterResponse = ApiResponse<{
    user: User;
    token: string;
}>;

/**
 * Auth state interface
 */
export interface AuthState {
    user: User | null;
    isAuthenticated: boolean;
    token: string | null;
    loading: boolean;
    error: DashboardError | null;
}

/**
 * Auth context interface
 */
export interface AuthContext extends FeatureContext {
    state: AuthState;
    login: (credentials: LoginRequest) => Promise<LoginResponse>;
    logout: () => Promise<void>;
    register: (data: RegisterRequest) => Promise<RegisterResponse>;
    refreshToken: () => Promise<string>;
}

/**
 * Auth event types
 */
export enum AuthEventType {
    LOGIN_REQUEST = 'AUTH_LOGIN_REQUEST',
    LOGIN_SUCCESS = 'AUTH_LOGIN_SUCCESS',
    LOGIN_ERROR = 'AUTH_LOGIN_ERROR',
    REGISTER_REQUEST = 'AUTH_REGISTER_REQUEST',
    REGISTER_SUCCESS = 'AUTH_REGISTER_SUCCESS',
    REGISTER_ERROR = 'AUTH_REGISTER_ERROR',
    LOGOUT = 'AUTH_LOGOUT',
    TOKEN_REFRESH = 'AUTH_TOKEN_REFRESH',
    SESSION_EXPIRED = 'AUTH_SESSION_EXPIRED'
}

/**
 * Auth event payloads
 */
export interface AuthEventPayloads {
    [AuthEventType.LOGIN_REQUEST]: LoginRequest;
    [AuthEventType.LOGIN_SUCCESS]: LoginResponse;
    [AuthEventType.LOGIN_ERROR]: DashboardError;
    [AuthEventType.REGISTER_REQUEST]: RegisterRequest;
    [AuthEventType.REGISTER_SUCCESS]: RegisterResponse;
    [AuthEventType.REGISTER_ERROR]: DashboardError;
    [AuthEventType.LOGOUT]: void;
    [AuthEventType.TOKEN_REFRESH]: string;
    [AuthEventType.SESSION_EXPIRED]: void;
}

/**
 * Auth events
 */
export type AuthEvent<T extends AuthEventType> = DashboardEvent<AuthEventPayloads[T]>;

/**
 * Auth error codes
 */
export enum AuthErrorCode {
    INVALID_CREDENTIALS = 'INVALID_CREDENTIALS',
    REGISTRATION_FAILED = 'REGISTRATION_FAILED',
    INVALID_INVITE_CODE = 'INVALID_INVITE_CODE',
    SESSION_EXPIRED = 'SESSION_EXPIRED',
    RATE_LIMIT_EXCEEDED = 'RATE_LIMIT_EXCEEDED',
    TOKEN_REFRESH_FAILED = 'TOKEN_REFRESH_FAILED',
    NETWORK_ERROR = 'NETWORK_ERROR',
    INVALID_RESPONSE = 'INVALID_RESPONSE',
    UNKNOWN_ERROR = 'UNKNOWN_ERROR'
}

/**
 * Auth component props
 */
export interface LoginFormProps {
    onSuccess?: (response: LoginResponse) => void;
    onError?: (error: DashboardError) => void;
    redirectUrl?: string;
    className?: string;
}

export interface RegisterFormProps {
    onSuccess?: (response: RegisterResponse) => void;
    onError?: (error: DashboardError) => void;
    requireInviteCode?: boolean;
    className?: string;
}

/**
 * Auth hook return type
 */
export interface UseAuth {
    user: User | null;
    isAuthenticated: boolean;
    loading: boolean;
    error: DashboardError | null;
    login: (credentials: LoginRequest) => Promise<void>;
    logout: () => Promise<void>;
    register: (data: RegisterRequest) => Promise<void>;
}

export interface AuthResponse {
    success: boolean;
    data?: {
        token: string;
        user: {
            id: number;
            username: string;
            email: string;
            roles: string[];
        };
    };
    error?: {
        code: AuthErrorCode;
        message: string;
        details?: Record<string, any>;
    };
}

export interface User {
    id: number;
    username: string;
    email: string;
    roles: string[];
}

export interface LoginRequest {
    username: string;
    password: string;
}

export interface LoginResponse {
    success: boolean;
    user: User;
    token: string;
}

export interface RegisterRequest {
    username: string;
    email: string;
    password: string;
}

export interface RegisterResponse {
    success: boolean;
    user: User;
} 