import { useContext } from 'react';
import { UserContext } from '../../features/user/context/UserContext';
import { FeatureContext } from '../contracts/Feature';
import { UserData } from '../types/api';

/**
 * Hook to access the current user's data and authentication state.
 * This hook integrates with the User feature's context system to provide
 * consistent user data across the application.
 *
 * @param context - The feature context containing debug and configuration settings
 * @returns An object containing user data, loading state, and any error messages
 *
 * @example
 * ```typescript
 * const { user, isLoading, error } = useUser(context);
 *
 * if (isLoading) return <LoadingSpinner />;
 * if (error) return <ErrorMessage message={error} />;
 * if (!user) return <NotAuthenticated />;
 *
 * return <div>Welcome, {user.name}!</div>;
 * ```
 *
 * @throws {Error} If used outside of a UserProvider context
 */
export const useUser = (context: FeatureContext) => {
    if (context.debug) {
        console.log('[useUser] Accessing user context');
    }

    const userContext = useContext(UserContext);
    
    if (!userContext) {
        throw new Error('useUser must be used within a UserProvider');
    }

    if (context.debug) {
        console.log('[useUser] Current state:', {
            hasUser: !!userContext.user,
            isLoading: userContext.isLoading,
            hasError: !!userContext.error,
            isAuthenticated: userContext.isAuthenticated
        });
    }

    return {
        user: userContext.user ? {
            id: userContext.user.id,
            name: userContext.user.displayName,
            email: userContext.user.email,
            roles: userContext.user.roles
        } as UserData : null,
        isLoading: userContext.isLoading,
        error: userContext.error?.message || null
    };
}; 