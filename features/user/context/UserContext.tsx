import React, { createContext, useContext, useEffect, useState, useCallback } from 'react';

interface User {
    id: number;
    username: string;
    email: string;
    roles: string[];
}

interface UserState {
    user: User | null;
    isLoading: boolean;
    error: Error | null;
    isAuthenticated: boolean;
}

interface UserContextType extends UserState {
    checkAuth: () => Promise<boolean>;
    logout: () => Promise<void>;
}

const UserContext = createContext<UserContextType | null>(null);

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

    const checkAuth = useCallback(async () => {
        try {
            setState(prev => ({ ...prev, isLoading: true, error: null }));
            
            const response = await fetch('/wp-json/wp/v2/users/me', {
                headers: {
                    'X-WP-Nonce': window.athleteDashboardData.nonce
                }
            });

            if (!response.ok) {
                if (response.status === 401) {
                    setState({
                        user: null,
                        isLoading: false,
                        error: null,
                        isAuthenticated: false
                    });
                    return false;
                }
                throw new Error('Failed to fetch user data');
            }

            const userData = await response.json();
            const user: User = {
                id: userData.id,
                username: userData.username,
                email: userData.email,
                roles: userData.roles
            };

            setState({
                user,
                isLoading: false,
                error: null,
                isAuthenticated: true
            });
            return true;
        } catch (error) {
            console.error('Error checking auth:', error);
            setState(prev => ({
                ...prev,
                isLoading: false,
                error: error instanceof Error ? error : new Error('Unknown error'),
                isAuthenticated: false
            }));
            return false;
        }
    }, []);

    const logout = useCallback(async () => {
        try {
            setState(prev => ({ ...prev, isLoading: true }));
            
            const response = await fetch('/wp-login.php?action=logout', {
                method: 'POST',
                headers: {
                    'X-WP-Nonce': window.athleteDashboardData.nonce
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

            // Redirect to login page
            window.location.href = '/wp-login.php';
        } catch (error) {
            console.error('Error during logout:', error);
            setState(prev => ({
                ...prev,
                isLoading: false,
                error: error instanceof Error ? error : new Error('Unknown error')
            }));
        }
    }, []);

    useEffect(() => {
        checkAuth();
    }, [checkAuth]);

    const value: UserContextType = {
        ...state,
        checkAuth,
        logout
    };

    return (
        <UserContext.Provider value={value}>
            {children}
        </UserContext.Provider>
    );
}

export function useUser(): UserContextType {
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