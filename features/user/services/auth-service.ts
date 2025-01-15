import { User } from '../types';

interface WordPressUserData {
    id: number;
    username: string;
    email: string;
    name: string;
    first_name: string;
    last_name: string;
    roles: string[];
}

export class AuthService {
    private static instance: AuthService;
    private readonly baseUrl: string;

    private constructor() {
        this.baseUrl = '/wp-json';
    }

    public static getInstance(): AuthService {
        if (!AuthService.instance) {
            AuthService.instance = new AuthService();
        }
        return AuthService.instance;
    }

    private getNonce(): string {
        return window.athleteDashboardData?.nonce || '';
    }

    private transformUserData(wpUser: WordPressUserData): User {
        return {
            id: wpUser.id,
            username: wpUser.username || '',
            email: wpUser.email || '',
            displayName: wpUser.name || '',
            firstName: wpUser.first_name || '',
            lastName: wpUser.last_name || '',
            roles: wpUser.roles || []
        };
    }

    public async getCurrentUser(): Promise<User | null> {
        try {
            console.group('AuthService: Get Current User');
            const endpoint = `${this.baseUrl}/wp/v2/users/me`;
            const nonce = this.getNonce();
            
            console.log('API Endpoint:', endpoint);
            console.log('Nonce present:', !!nonce);
            
            const response = await fetch(endpoint, {
                headers: {
                    'X-WP-Nonce': nonce
                }
            });

            console.log('Response status:', response.status);
            
            if (!response.ok) {
                if (response.status === 401) {
                    console.log('User not authenticated');
                    return null;
                }
                throw new Error(`Failed to fetch user data: ${response.status} ${response.statusText}`);
            }

            const userData: WordPressUserData = await response.json();
            
            if (!userData?.id) {
                console.error('Invalid user data received:', userData);
                throw new Error('Invalid user data: missing ID');
            }

            const user = this.transformUserData(userData);
            console.log('User data transformed:', user);
            console.groupEnd();
            
            return user;
        } catch (error) {
            console.error('Error fetching user data:', error);
            console.groupEnd();
            throw error;
        }
    }

    public async logout(): Promise<void> {
        try {
            console.group('AuthService: Logout');
            const response = await fetch('/wp-login.php?action=logout', {
                method: 'POST',
                headers: {
                    'X-WP-Nonce': this.getNonce()
                }
            });

            if (!response.ok) {
                throw new Error('Logout failed');
            }

            console.log('Logout successful');
            window.location.href = '/wp-login.php';
        } catch (error) {
            console.error('Error during logout:', error);
            throw error;
        } finally {
            console.groupEnd();
        }
    }

    public async checkAuthentication(): Promise<boolean> {
        try {
            const user = await this.getCurrentUser();
            return !!user;
        } catch (error) {
            console.error('Error checking authentication:', error);
            return false;
        }
    }
} 