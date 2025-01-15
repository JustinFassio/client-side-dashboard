import React, { createContext, useContext, useEffect, useState, useCallback, useRef } from 'react';
import { User, UserState, UserContextValue } from '../types';
import { AuthService } from '../services/auth-service';

// Initialize with default values to avoid the _currentValue error
const defaultContextValue: UserContextValue = {
    user: null,
    isLoading: true,
    error: null,
    isAuthenticated: false,
    checkAuth: async () => false,
    logout: async () => {},
    refreshUser: async () => {}
};

export const UserContext = createContext<UserContextValue>(defaultContextValue);

interface UserProviderProps {
    children: React.ReactNode;
}

export function UserProvider({ children }: UserProviderProps) {
    const [state, setState] = useState<UserState>({
        user: null,
        isLoading: true,
        error: null,
        isAuthenticated: false
    });

    // Use refs to track request status and prevent duplicate requests
    const isFetchingRef = useRef(false);
    const lastFetchTimeRef = useRef(0);
    const MIN_FETCH_INTERVAL = 1000; // Minimum time between fetches in milliseconds

    const fetchUserData = useCallback(async () => {
        // Prevent concurrent fetches and throttle requests
        const now = Date.now();
        if (isFetchingRef.current || (now - lastFetchTimeRef.current) < MIN_FETCH_INTERVAL) {
            console.log('Skipping fetch: too soon or already in progress');
            return null;
        }

        isFetchingRef.current = true;
        lastFetchTimeRef.current = now;

        try {
            const authService = AuthService.getInstance();
            const user = await authService.getCurrentUser();
            return user;
        } catch (error) {
            console.error('Error fetching user data:', error);
            throw error;
        } finally {
            isFetchingRef.current = false;
        }
    }, []); // No dependencies needed as it only uses refs

    const checkAuth = useCallback(async () => {
        // Skip if already authenticated
        if (state.isAuthenticated && state.user) {
            console.log('Already authenticated with user data, skipping check');
            return true;
        }

        try {
            console.group('UserContext: Check Auth');
            console.log('Current state:', state);
            
            // Only set loading if not already loading
            if (!state.isLoading) {
                setState(prev => ({ ...prev, isLoading: true, error: null }));
            }
            
            const user = await fetchUserData();
            console.log('Fetch user data result:', user);
            
            if (!user) {
                console.log('No user data, setting unauthenticated state');
                setState({
                    user: null,
                    isLoading: false,
                    error: null,
                    isAuthenticated: false
                });
                return false;
            }

            console.log('User authenticated, updating state');
            setState({
                user,
                isLoading: false,
                error: null,
                isAuthenticated: true
            });
            return true;
        } catch (error) {
            console.error('Error checking auth:', error);
            setState({
                user: null,
                isLoading: false,
                error: error instanceof Error ? error : new Error('Unknown error'),
                isAuthenticated: false
            });
            return false;
        } finally {
            console.groupEnd();
        }
    }, [fetchUserData, state.isAuthenticated, state.isLoading, state.user]);

    const refreshUser = useCallback(async () => {
        // Skip if already loading
        if (state.isLoading) {
            console.log('Skipping refresh: already loading');
            return;
        }

        try {
            console.group('UserContext: Refresh User');
            const user = await fetchUserData();
            if (user) {
                console.log('User data refreshed:', user);
                setState(prev => ({
                    ...prev,
                    user,
                    error: null,
                    isAuthenticated: true
                }));
            } else {
                console.log('No user data after refresh');
                setState(prev => ({
                    ...prev,
                    user: null,
                    error: null,
                    isAuthenticated: false
                }));
            }
        } catch (error) {
            console.error('Error refreshing user data:', error);
            setState(prev => ({
                ...prev,
                error: error instanceof Error ? error : new Error('Failed to refresh user data')
            }));
        } finally {
            console.groupEnd();
        }
    }, [fetchUserData, state.isLoading]);

    const logout = useCallback(async () => {
        if (state.isLoading) {
            console.log('Skipping logout: already loading');
            return;
        }

        try {
            console.group('UserContext: Logout');
            setState(prev => ({ ...prev, isLoading: true }));
            
            const authService = AuthService.getInstance();
            await authService.logout();

            setState({
                user: null,
                isLoading: false,
                error: null,
                isAuthenticated: false
            });
        } catch (error) {
            console.error('Error during logout:', error);
            setState(prev => ({
                ...prev,
                isLoading: false,
                error: error instanceof Error ? error : new Error('Unknown error')
            }));
        } finally {
            console.groupEnd();
        }
    }, [state.isLoading]);

    // Initial auth check - only run once on mount
    useEffect(() => {
        console.log('UserContext: Initial auth check');
        checkAuth().catch(error => {
            console.error('Error during initial auth check:', error);
            setState({
                user: null,
                isLoading: false,
                error: error instanceof Error ? error : new Error('Failed to check authentication'),
                isAuthenticated: false
            });
        });
    }, [checkAuth]); // Added checkAuth as dependency

    const value = {
        ...state,
        checkAuth,
        logout,
        refreshUser
    };

    return (
        <UserContext.Provider value={value}>
            {children}
        </UserContext.Provider>
    );
}

export function useUser(): UserContextValue {
    const context = useContext(UserContext);
    if (!context) {
        throw new Error('useUser must be used within a UserProvider');
    }
    return context;
}

export function RequireAuth({ children }: { children: React.ReactNode }) {
    const { isAuthenticated, isLoading } = useUser();

    useEffect(() => {
        if (!isLoading && !isAuthenticated) {
            window.location.href = '/wp-login.php';
        }
    }, [isAuthenticated, isLoading]);

    if (isLoading) {
        return <div>Loading...</div>;
    }

    if (!isAuthenticated) {
        return null;
    }

    return <>{children}</>;
} 