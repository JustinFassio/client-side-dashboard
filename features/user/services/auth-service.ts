import { User } from '../types';

interface WordPressUserData {
    id: number;
    user_login?: string;    // WordPress field
    username?: string;      // Our normalized field
    user_email?: string;    // WordPress field
    email?: string;        // Our normalized field
    display_name?: string;  // WordPress field
    name?: string;         // Our normalized field
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
        console.group('AuthService: Transform User Data');
        console.log('Raw WordPress user data:', {
            id: wpUser.id,
            user_login: wpUser.user_login,
            username: wpUser.username,
            user_email: wpUser.user_email,
            email: wpUser.email,
            display_name: wpUser.display_name,
            name: wpUser.name,
            first_name: wpUser.first_name,
            last_name: wpUser.last_name,
            roles: wpUser.roles
        });
        
        const user = {
            id: wpUser.id,
            username: wpUser.user_login || wpUser.username || '',
            email: wpUser.user_email || wpUser.email || '',
            displayName: wpUser.display_name || wpUser.name || '',
            firstName: wpUser.first_name || '',
            lastName: wpUser.last_name || '',
            roles: wpUser.roles || []
        };
        
        console.log('Field mapping results:', {
            username: {
                from_user_login: wpUser.user_login,
                from_username: wpUser.username,
                final: user.username
            },
            email: {
                from_user_email: wpUser.user_email,
                from_email: wpUser.email,
                final: user.email
            },
            displayName: {
                from_display_name: wpUser.display_name,
                from_name: wpUser.name,
                final: user.displayName
            },
            firstName: {
                value: user.firstName,
                source: wpUser.first_name
            },
            lastName: {
                value: user.lastName,
                source: wpUser.last_name
            }
        });
        
        console.log('Transformed user data:', user);
        console.groupEnd();
        return user;
    }

    public async getCurrentUser(): Promise<User | null> {
        try {
            console.group('AuthService: Get Current User');
            const endpoint = `${this.baseUrl}/wp/v2/users/me?context=edit`;
            const nonce = this.getNonce();
            
            console.log('Request details:', {
                endpoint,
                hasNonce: !!nonce,
                baseUrl: this.baseUrl
            });
            
            const response = await fetch(endpoint, {
                headers: {
                    'X-WP-Nonce': nonce
                }
            });

            console.log('Response details:', {
                status: response.status,
                statusText: response.statusText,
                ok: response.ok
            });
            
            if (!response.ok) {
                if (response.status === 401) {
                    console.log('User not authenticated');
                    console.groupEnd();
                    return null;
                }
                throw new Error(`Failed to fetch user data: ${response.status} ${response.statusText}`);
            }

            const userData: WordPressUserData = await response.json();
            console.log('Raw API response:', userData);
            
            if (!userData?.id) {
                console.error('Invalid user data received:', userData);
                throw new Error('Invalid user data: missing ID');
            }

            const user = {
                id: userData.id,
                username: userData.user_login || userData.username || '',
                email: userData.user_email || userData.email || '',
                displayName: userData.display_name || userData.name || '',
                firstName: userData.first_name || '',
                lastName: userData.last_name || '',
                roles: userData.roles || []
            };

            console.log('Transformed user data:', user);
            console.groupEnd();
            
            return user;
        } catch (error) {
            console.error('Error in getCurrentUser:', error);
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