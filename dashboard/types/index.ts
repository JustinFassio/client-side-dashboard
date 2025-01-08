/**
 * Core type definitions for the Athlete Dashboard
 */

/**
 * Base response type for all API endpoints
 */
export interface ApiResponse<T = any> {
    success: boolean;
    data: T;
    error?: {
        code: string;
        message: string;
        details?: Record<string, any>;
    };
}

/**
 * Base configuration interface for features
 */
export interface FeatureConfig {
    enabled: boolean;
    debug?: boolean;
    version?: string;
}

/**
 * Base event interface for all dashboard events
 */
export interface DashboardEvent<T = any> {
    type: string;
    payload: T;
    timestamp: number;
    source: string;
}

/**
 * Base user interface
 */
export interface User {
    id: number;
    username: string;
    email: string;
    firstName?: string;
    lastName?: string;
    roles: string[];
    meta: Record<string, any>;
}

/**
 * Common status types
 */
export type Status = 'idle' | 'loading' | 'success' | 'error';

/**
 * Base error interface
 */
export interface DashboardError extends Error {
    code: string;
    details?: Record<string, any>;
    timestamp: number;
}

/**
 * Base context interface for features
 */
export interface FeatureContext {
    debug: boolean;
    dispatch: (scope: string) => (event: DashboardEvent) => void;
    getState: () => Record<string, any>;
    subscribe: (listener: (state: Record<string, any>) => void) => () => void;
}

/**
 * Base metadata interface for features
 */
export interface FeatureMetadata {
    name: string;
    description: string;
    version: string;
    dependencies?: string[];
    order?: number;
}

/**
 * Common validation result interface
 */
export interface ValidationResult {
    valid: boolean;
    errors?: Record<string, string[]>;
}

/**
 * Common pagination parameters
 */
export interface PaginationParams {
    page: number;
    perPage: number;
    orderBy?: string;
    order?: 'asc' | 'desc';
}

/**
 * Common response metadata
 */
export interface ResponseMetadata {
    total: number;
    totalPages: number;
    currentPage: number;
    perPage: number;
}

/**
 * Base notification interface
 */
export interface Notification {
    id: string;
    type: 'info' | 'success' | 'warning' | 'error';
    message: string;
    title?: string;
    timestamp: number;
    read?: boolean;
    data?: Record<string, any>;
}

/**
 * Common date range type
 */
export type DateRange = '7d' | '30d' | '90d' | 'custom';

/**
 * Base permission interface
 */
export interface Permission {
    action: string;
    resource: string;
    conditions?: Record<string, any>;
}

/**
 * Common theme type
 */
export type Theme = 'light' | 'dark' | 'system';

/**
 * Base preferences interface
 */
export interface Preferences {
    theme: Theme;
    language: string;
    timezone: string;
    notifications: {
        email: boolean;
        push: boolean;
        inApp: boolean;
    };
}

/**
 * Common HTTP methods
 */
export type HttpMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';

/**
 * Base API endpoint configuration
 */
export interface ApiEndpoint {
    path: string;
    method: HttpMethod;
    requiresAuth: boolean;
    rateLimit?: {
        requests: number;
        period: number;
    };
}

/**
 * Common cache configuration
 */
export interface CacheConfig {
    enabled: boolean;
    ttl: number;
    prefix?: string;
    invalidationEvents?: string[];
}

/**
 * Base widget configuration
 */
export interface WidgetConfig {
    id: string;
    title: string;
    enabled: boolean;
    refreshInterval?: number;
    position?: {
        x: number;
        y: number;
        width: number;
        height: number;
    };
} 