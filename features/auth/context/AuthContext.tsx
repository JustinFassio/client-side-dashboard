/**
 * @fileoverview AuthContext provides authentication state and methods throughout the application.
 * It manages user authentication state, login/logout operations, and session handling.
 * @module features/auth/context/AuthContext
 */

import React, { createContext, useContext, useState, useCallback, useEffect } from 'react';
import { EventEmitter } from '@/dashboard/events';
import { AuthService } from '../services/AuthService';

/**
 * Interface defining the shape of the authentication state.
 * @interface AuthState
 */
interface AuthState {
    /** Whether the user is currently authenticated */
    isAuthenticated: boolean;
    /** The current user's data */
    user: any | null;
    /** Whether authentication state is being loaded */
    isLoading: boolean;
    /** Any authentication-related error */
    error: Error | null;
}

/**
 * Interface defining the authentication context value.
 * @interface AuthContextValue
 * @extends AuthState
 */
interface AuthContextValue extends AuthState {
    /** Function to log in a user */
    login: (credentials: { username: string; password: string }) => Promise<void>;
    /** Function to log out the current user */
    logout: () => Promise<void>;
    /** Function to refresh the authentication session */
    refreshSession: () => Promise<void>;
    /** Function to clear any authentication errors */
    clearError: () => void;
}

/**
 * The default authentication context state.
 * @type {AuthContextValue}
 */
const defaultContext: AuthContextValue = {
    isAuthenticated: false,
    user: null,
    isLoading: true,
    error: null,
    login: async () => {},
    logout: async () => {},
    refreshSession: async () => {},
    clearError: () => {}
};

/**
 * Context for managing authentication state.
 * @type {React.Context<AuthContextValue>}
 */
const AuthContext = createContext<AuthContextValue>(defaultContext);

/**
 * Hook for accessing the authentication context.
 * @returns {AuthContextValue} The authentication context value
 * @throws {Error} If used outside of AuthProvider
 */
export const useAuth = (): AuthContextValue => {
    const context = useContext(AuthContext);
    if (!context) {
        throw new Error('useAuth must be used within an AuthProvider');
    }
    return context;
};

/**
 * Props for the AuthProvider component.
 * @interface AuthProviderProps
 */
interface AuthProviderProps {
    /** Child components that will have access to the auth context */
    children: React.ReactNode;
}

/**
 * Provider component for authentication context.
 * Manages authentication state and provides methods for authentication operations.
 * @param {AuthProviderProps} props - The component props
 * @returns {JSX.Element} The provider component
 */
export const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
    const [state, setState] = useState<AuthState>({
        isAuthenticated: false,
        user: null,
        isLoading: true,
        error: null
    });

    /**
     * Updates the authentication state.
     * @param {Partial<AuthState>} updates - The state updates to apply
     */
    const updateState = useCallback((updates: Partial<AuthState>) => {
        setState(prev => ({ ...prev, ...updates }));
    }, []);

    /**
     * Handles successful authentication.
     * @param {any} user - The authenticated user data
     */
    const handleAuthSuccess = useCallback((user: any) => {
        updateState({
            isAuthenticated: true,
            user,
            isLoading: false,
            error: null
        });
    }, [updateState]);

    /**
     * Handles authentication errors.
     * @param {Error} error - The error that occurred
     */
    const handleAuthError = useCallback((error: Error) => {
        updateState({
            isAuthenticated: false,
            user: null,
            isLoading: false,
            error
        });
    }, [updateState]);

    /**
     * Attempts to log in a user with the provided credentials.
     * @param {{ username: string; password: string }} credentials - The login credentials
     * @returns {Promise<void>}
     */
    const login = async (credentials: { username: string; password: string }): Promise<void> => {
        try {
            updateState({ isLoading: true, error: null });
            const user = await AuthService.login(credentials);
            handleAuthSuccess(user);
            EventEmitter.emit('AUTH_LOGIN_SUCCESS', user);
        } catch (error) {
            handleAuthError(error as Error);
            EventEmitter.emit('AUTH_ERROR', error);
            throw error;
        }
    };

    /**
     * Logs out the current user.
     * @returns {Promise<void>}
     */
    const logout = async (): Promise<void> => {
        try {
            await AuthService.logout();
            updateState({
                isAuthenticated: false,
                user: null,
                isLoading: false,
                error: null
            });
            EventEmitter.emit('AUTH_LOGOUT');
        } catch (error) {
            handleAuthError(error as Error);
            EventEmitter.emit('AUTH_ERROR', error);
            throw error;
        }
    };

    /**
     * Refreshes the current authentication session.
     * @returns {Promise<void>}
     */
    const refreshSession = async (): Promise<void> => {
        try {
            const user = await AuthService.refreshSession();
            handleAuthSuccess(user);
        } catch (error) {
            handleAuthError(error as Error);
            EventEmitter.emit('AUTH_SESSION_EXPIRED');
            throw error;
        }
    };

    /**
     * Clears any authentication errors.
     */
    const clearError = useCallback(() => {
        updateState({ error: null });
    }, [updateState]);

    /**
     * Sets up event listeners for authentication events.
     */
    useEffect(() => {
        const handleLoginSuccess = (user: any) => handleAuthSuccess(user);
        const handleError = (error: Error) => handleAuthError(error);

        EventEmitter.on('AUTH_LOGIN_SUCCESS', handleLoginSuccess);
        EventEmitter.on('AUTH_ERROR', handleError);

        return () => {
            EventEmitter.off('AUTH_LOGIN_SUCCESS', handleLoginSuccess);
            EventEmitter.off('AUTH_ERROR', handleError);
        };
    }, [handleAuthSuccess, handleAuthError]);

    /**
     * Checks initial authentication state.
     */
    useEffect(() => {
        const checkAuth = async () => {
            try {
                const isAuthenticated = await AuthService.checkAuth();
                if (isAuthenticated) {
                    const user = await AuthService.getCurrentUser();
                    handleAuthSuccess(user);
                } else {
                    updateState({ isLoading: false });
                }
            } catch (error) {
                handleAuthError(error as Error);
            }
        };

        checkAuth();
    }, [handleAuthSuccess, handleAuthError, updateState]);

    const value = {
        ...state,
        login,
        logout,
        refreshSession,
        clearError
    };

    return (
        <AuthContext.Provider value={value}>
            {children}
        </AuthContext.Provider>
    );
}; 