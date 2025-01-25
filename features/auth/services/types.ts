import { HttpMethod } from '../../../dashboard/types';

/**
 * Configuration for the auth service API.
 */
export interface ApiConfig {
    /** Base URL for all API endpoints */
    baseUrl: string;
    /** API endpoint paths */
    endpoints: {
        login: string;
        logout: string;
        register: string;
        refresh: string;
        user: string;
    };
    /** Default headers to include with all requests */
    headers: Record<string, string>;
}

/**
 * Options for making HTTP requests.
 */
export interface RequestOptions {
    /** HTTP method to use */
    method: HttpMethod;
    /** Request headers */
    headers?: Record<string, string>;
    /** Request body */
    body?: string | FormData | URLSearchParams;
    /** Credentials mode */
    credentials?: RequestCredentials;
}

/**
 * Standard API error response.
 */
export interface ApiError {
    /** Error code */
    code: string;
    /** Error message */
    message: string;
    /** Additional error details */
    details?: Record<string, unknown>;
}

/**
 * Rate limiting configuration.
 */
export interface RateLimitConfig {
    /** Maximum number of attempts allowed */
    maxAttempts: number;
    /** Time window in milliseconds */
    windowMs: number;
    /** Duration of block in milliseconds */
    blockDurationMs: number;
}

/**
 * Rate limiting state.
 */
export interface RateLimitState {
    /** Number of attempts made */
    attempts: number;
    /** Start time of current window */
    windowStart: number;
    /** Whether the client is blocked */
    blocked: boolean;
    /** When the block expires */
    blockExpiry?: number;
}

/**
 * Configuration for token management.
 */
export interface TokenConfig {
    /** Storage key for the token */
    storageKey: string;
    /** Token expiration time in seconds */
    expirationTime: number;
    /** Refresh threshold in seconds */
    refreshThreshold: number;
}

/**
 * Token data structure.
 */
export interface TokenData {
    /** The actual token string */
    token: string;
    /** When the token expires */
    expiresAt: number;
    /** Token type (e.g., 'Bearer') */
    type: string;
}

/**
 * Request retry configuration.
 */
export interface RetryConfig {
    /** Maximum number of retry attempts */
    maxRetries: number;
    /** Base delay between retries in milliseconds */
    baseDelay: number;
    /** Maximum delay between retries in milliseconds */
    maxDelay: number;
    /** Whether to use exponential backoff */
    useExponentialBackoff: boolean;
}

/**
 * Service event handler type.
 */
export type ServiceEventHandler<T = unknown> = (data: T) => void | Promise<void>;

/**
 * Service event subscription.
 */
export interface ServiceEventSubscription {
    /** Unsubscribe from the event */
    unsubscribe: () => void;
}

/**
 * Service initialization options.
 */
export interface ServiceInitOptions {
    /** Enable debug mode */
    debug?: boolean;
    /** Custom API configuration */
    apiConfig?: Partial<ApiConfig>;
    /** Custom rate limit configuration */
    rateLimitConfig?: Partial<RateLimitConfig>;
    /** Custom token configuration */
    tokenConfig?: Partial<TokenConfig>;
    /** Custom retry configuration */
    retryConfig?: Partial<RetryConfig>;
} 