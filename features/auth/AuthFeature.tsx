/**
 * @fileoverview AuthFeature component manages authentication functionality for the Athlete Dashboard.
 * It handles user authentication, session management, and integrates with WordPress authentication.
 * @module features/auth/AuthFeature
 */

import { Feature } from '@/dashboard/Feature';
import { EventEmitter } from '@/dashboard/events';
import { AuthService } from './services/AuthService';
import { AuthProvider } from './context/AuthContext';

/**
 * Interface defining the configuration options for the AuthFeature.
 * @interface AuthFeatureConfig
 */
interface AuthFeatureConfig {
    /** Duration in seconds before a session times out */
    sessionTimeout: number;
    /** Threshold in seconds before session refresh */
    refreshThreshold: number;
    /** Maximum number of retry attempts for failed operations */
    maxRetries: number;
}

/**
 * AuthFeature class manages authentication functionality for the Athlete Dashboard.
 * It extends the base Feature class and implements authentication-specific logic.
 * @class
 * @extends Feature
 */
export class AuthFeature extends Feature {
    /** Configuration settings for the auth feature */
    private config: AuthFeatureConfig;
    /** Timer ID for session refresh */
    private refreshTimer: NodeJS.Timeout | null;

    /**
     * Creates an instance of AuthFeature.
     * @param {Partial<AuthFeatureConfig>} [config] - Optional configuration settings
     */
    constructor(config?: Partial<AuthFeatureConfig>) {
        super('auth');
        this.config = {
            sessionTimeout: 3600,
            refreshThreshold: 300,
            maxRetries: 3,
            ...config
        };
        this.refreshTimer = null;
    }

    /**
     * Initializes the auth feature and sets up event listeners.
     * @returns {Promise<void>}
     */
    async initialize(): Promise<void> {
        try {
            await this.checkInitialAuth();
            this.setupEventListeners();
            this.startSessionRefresh();
        } catch (error) {
            EventEmitter.emit('AUTH_ERROR', error);
        }
    }

    /**
     * Checks the initial authentication state when the feature loads.
     * @private
     * @returns {Promise<void>}
     */
    private async checkInitialAuth(): Promise<void> {
        try {
            const isAuthenticated = await AuthService.checkAuth();
            if (isAuthenticated) {
                const user = await AuthService.getCurrentUser();
                EventEmitter.emit('AUTH_LOGIN_SUCCESS', user);
            }
        } catch (error) {
            EventEmitter.emit('AUTH_ERROR', error);
        }
    }

    /**
     * Sets up event listeners for authentication-related events.
     * @private
     */
    private setupEventListeners(): void {
        EventEmitter.on('AUTH_LOGIN_SUCCESS', this.handleLoginSuccess);
        EventEmitter.on('AUTH_LOGOUT', this.handleLogout);
        EventEmitter.on('AUTH_SESSION_EXPIRED', this.handleSessionExpired);
        EventEmitter.on('NAVIGATION_CHANGED', this.handleNavigation);
    }

    /**
     * Starts the session refresh timer to maintain authentication.
     * @private
     */
    private startSessionRefresh(): void {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
        }

        this.refreshTimer = setInterval(
            () => this.refreshSession(),
            this.config.refreshThreshold * 1000
        );
    }

    /**
     * Handles successful login events.
     * @private
     * @param {any} user - The authenticated user data
     */
    private handleLoginSuccess = (user: any): void => {
        this.startSessionRefresh();
        // Additional login success handling
    };

    /**
     * Handles logout events.
     * @private
     */
    private handleLogout = (): void => {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
        }
        // Additional logout handling
    };

    /**
     * Handles session expiration events.
     * @private
     */
    private handleSessionExpired = (): void => {
        this.handleLogout();
        EventEmitter.emit('AUTH_ERROR', new Error('Session expired'));
    };

    /**
     * Handles navigation events to check authentication requirements.
     * @private
     * @param {string} route - The new route being navigated to
     */
    private handleNavigation = (route: string): void => {
        // Check if route requires authentication
        if (this.requiresAuth(route)) {
            this.checkInitialAuth();
        }
    };

    /**
     * Checks if a route requires authentication.
     * @private
     * @param {string} route - The route to check
     * @returns {boolean} Whether the route requires authentication
     */
    private requiresAuth(route: string): boolean {
        const publicRoutes = ['/login', '/register', '/forgot-password'];
        return !publicRoutes.includes(route);
    }

    /**
     * Refreshes the authentication session.
     * @private
     * @returns {Promise<void>}
     */
    private async refreshSession(): Promise<void> {
        try {
            await AuthService.refreshSession();
        } catch (error) {
            this.handleSessionExpired();
        }
    }

    /**
     * Renders the auth feature component.
     * @returns {JSX.Element} The rendered component
     */
    render(): JSX.Element {
        return (
            <AuthProvider>
                {this.props.children}
            </AuthProvider>
        );
    }

    /**
     * Cleans up resources when the feature is unloaded.
     */
    cleanup(): void {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
        }

        EventEmitter.off('AUTH_LOGIN_SUCCESS', this.handleLoginSuccess);
        EventEmitter.off('AUTH_LOGOUT', this.handleLogout);
        EventEmitter.off('AUTH_SESSION_EXPIRED', this.handleSessionExpired);
        EventEmitter.off('NAVIGATION_CHANGED', this.handleNavigation);
    }
} 