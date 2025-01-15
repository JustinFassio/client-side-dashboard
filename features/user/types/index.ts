export interface User {
    id: number;
    username: string;
    email: string;
    displayName: string;
    firstName: string;
    lastName: string;
    roles: string[];
}

export interface UserState {
    user: User | null;
    isLoading: boolean;
    error: Error | null;
    isAuthenticated: boolean;
}

export interface UserContextValue extends UserState {
    checkAuth: () => Promise<boolean>;
    logout: () => Promise<void>;
    refreshUser: () => Promise<void>;
} 