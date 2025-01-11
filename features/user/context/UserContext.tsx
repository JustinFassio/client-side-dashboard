import React, { createContext, useContext, useEffect, useState, useCallback, useRef, useMemo } from 'react';

interface User {
    id: number;
    username: string;
    email: string;
    displayName: string;
    firstName: string;
    lastName: string;
    roles: string[];
}

interface UserState {
    user: User | null;
    isLoading: boolean;
    error: Error | null;
    isAuthenticated: boolean;
}

interface UserContextValue extends UserState {
    checkAuth: () => Promise<boolean>;
    logout: () => Promise<void>;
    refreshUser: () => Promise<void>;
}

const UserContext = createContext<UserContextValue | null>(null);

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
            console.group('UserContext: Fetch User Data');
            const endpoint = '/wp-json/wp/v2/users/me';
            const nonce = window.athleteDashboardData?.nonce || '';
            
            console.log('API Endpoint:', endpoint);
            console.log('athleteDashboardData:', window.athleteDashboardData);
            console.log('Nonce present:', !!nonce);
            
            const response = await fetch(endpoint, {
                headers: {
                    'X-WP-Nonce': nonce
                }
            });

            console.log('Response status:', response.status);
            console.log('Response headers:', Object.fromEntries(response.headers.entries()));
            
            const responseText = await response.text();
            console.log('Raw response:', responseText);

            if (!response.ok) {
                if (response.status === 401) {
                    console.log('User not authenticated - redirecting to login');
                    window.location.href = '/wp-login.php';
                    return null;
                }
                throw new Error(`Failed to fetch user data: ${response.status} ${response.statusText}`);
            }

            let userData;
            try {
                userData = JSON.parse(responseText);
            } catch (parseError) {
                console.error('Error parsing user data:', parseError);
                throw new Error('Invalid JSON response from server');
            }

            if (!userData?.id) {
                console.error('Invalid user data received:', userData);
                throw new Error('Invalid user data: missing ID');
            }

            console.log('User data received:', userData);
            
            const user: User = {
                id: userData.id,
                username: userData.username || '',
                email: userData.email || '',
                displayName: userData.name || '',
                firstName: userData.first_name || '',
                lastName: userData.last_name || '',
                roles: userData.roles || []
            };

            console.log('Normalized user data:', user);
            console.groupEnd();
            return user;
        } catch (error) {
            console.error('Error fetching user data:', error);
            console.groupEnd();
            throw error;
        } finally {
            isFetchingRef.current = false;
        }
    }, []); // No dependencies needed as it only uses refs and window object

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
            
            const response = await fetch('/wp-login.php?action=logout', {
                method: 'POST',
                headers: {
                    'X-WP-Nonce': window.athleteDashboardData?.nonce || ''
                }
            });

            if (!response.ok) {
                throw new Error('Logout failed');
            }

            setState({
                user: null,
                isLoading: false,
                error: null,
                isAuthenticated: false
            });

            window.location.href = '/wp-login.php';
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

    // Debug logging for state changes - but throttle to prevent spam
    const lastLogTimeRef = useRef(0);
    useEffect(() => {
        const now = Date.now();
        if (now - lastLogTimeRef.current < 1000) {
            return; // Skip logging if less than 1 second has passed
        }
        lastLogTimeRef.current = now;

        console.group('UserContext: State Change');
        console.log('User:', state.user);
        console.log('Is Loading:', state.isLoading);
        console.log('Is Authenticated:', state.isAuthenticated);
        console.log('Error:', state.error);
        console.groupEnd();
    }, [state]);

    const value = useMemo(() => ({
        ...state,
        checkAuth,
        logout,
        refreshUser
    }), [state, checkAuth, logout, refreshUser]);

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