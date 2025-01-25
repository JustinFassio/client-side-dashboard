import { 
    LoginRequest, 
    LoginResponse, 
    RegisterRequest, 
    RegisterResponse, 
    AuthEventType,
    AuthErrorCode
} from '../types';
import { User } from '../../../dashboard/types';
import { AuthServiceError, shouldRetry } from './errors';
import { ApiConfig, RequestOptions, RetryConfig } from './types';
import { dashboardEvents } from '../../../dashboard/events';
import { RateLimiter } from './rateLimiting';

/**
 * AuthService handles all authentication-related operations.
 * It provides methods for login, logout, registration, and session management.
 */
export class AuthService {
    private static config: ApiConfig = {
        baseUrl: '/wp-json/athlete-dashboard/v1',
        endpoints: {
            login: '/auth/login',
            logout: '/auth/logout',
            register: '/auth/register',
            refresh: '/auth/refresh',
            user: '/profile'
        },
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': window.athleteDashboardData?.nonce || ''
        }
    };

    private static retryConfig: RetryConfig = {
        maxRetries: 3,
        baseDelay: 1000,
        maxDelay: 5000,
        useExponentialBackoff: true
    };

    private static rateLimiter = RateLimiter.getInstance();

    /**
     * Authenticates a user with the provided credentials.
     * @param credentials The login credentials
     * @returns A promise that resolves with the login response
     * @throws AuthServiceError if authentication fails
     */
    public static async login(credentials: LoginRequest): Promise<LoginResponse> {
        const key = `login:${credentials.username}`;
        
        try {
            this.rateLimiter.checkRateLimit(key);
            
            const response = await this.makeRequest<LoginResponse>(
                this.config.endpoints.login,
                {
                    method: 'POST',
                    body: JSON.stringify(credentials)
                }
            );

            this.validateResponse(response);
            this.rateLimiter.resetState(key);
            dashboardEvents.emit(AuthEventType.LOGIN_SUCCESS, response);
            return response;
        } catch (error) {
            this.rateLimiter.incrementAttempts(key);
            const authError = this.handleError(error);
            dashboardEvents.emit(AuthEventType.LOGIN_ERROR, authError);
            throw authError;
        }
    }

    /**
     * Logs out the current user.
     * @throws AuthServiceError if logout fails
     */
    public static async logout(): Promise<void> {
        try {
            await this.makeRequest(this.config.endpoints.logout, {
                method: 'POST'
            });
            dashboardEvents.emit(AuthEventType.LOGOUT);
        } catch (error) {
            const authError = this.handleError(error);
            dashboardEvents.emit(AuthEventType.LOGIN_ERROR, authError);
            throw authError;
        }
    }

    /**
     * Registers a new user with the provided data.
     * @param data The registration data
     * @returns A promise that resolves with the registration response
     * @throws AuthServiceError if registration fails
     */
    public static async register(data: RegisterRequest): Promise<RegisterResponse> {
        const key = `register:${data.email}`;
        
        try {
            this.rateLimiter.checkRateLimit(key);
            
            const response = await this.makeRequest<RegisterResponse>(
                this.config.endpoints.register,
                {
                    method: 'POST',
                    body: JSON.stringify(data)
                }
            );

            this.validateResponse(response);
            this.rateLimiter.resetState(key);
            dashboardEvents.emit(AuthEventType.REGISTER_SUCCESS, response);
            return response;
        } catch (error) {
            this.rateLimiter.incrementAttempts(key);
            const authError = this.handleError(error);
            dashboardEvents.emit(AuthEventType.REGISTER_ERROR, authError);
            throw authError;
        }
    }

    /**
     * Checks if the current user is authenticated.
     * @returns A promise that resolves with the authentication status
     */
    public static async checkAuth(): Promise<boolean> {
        try {
            const user = await this.getCurrentUser();
            return !!user;
        } catch {
            return false;
        }
    }

    /**
     * Retrieves the current user's data.
     * @returns A promise that resolves with the user data
     * @throws AuthServiceError if the request fails
     */
    public static async getCurrentUser(): Promise<User> {
        try {
            const response = await this.makeRequest<{ profile: { user: User } }>(
                this.config.endpoints.user,
                { method: 'GET' }
            );

            this.validateResponse(response);

            if (!response.profile?.user) {
                throw new AuthServiceError(
                    AuthErrorCode.INVALID_RESPONSE,
                    'Invalid user data in response'
                );
            }

            return response.profile.user;
        } catch (error) {
            console.error('Failed to get current user:', error);
            throw this.handleError(error);
        }
    }

    /**
     * Refreshes the authentication token.
     * @returns A promise that resolves with the new token
     * @throws AuthServiceError if the refresh fails
     */
    public static async refreshToken(): Promise<string> {
        try {
            const response = await this.makeRequest<{ token: string }>(
                this.config.endpoints.refresh,
                { method: 'POST' }
            );

            this.validateResponse(response);
            return response.token;
        } catch (error) {
            const authError = this.handleError(error);
            dashboardEvents.emit(AuthEventType.SESSION_EXPIRED);
            throw authError;
        }
    }

    /**
     * Makes an HTTP request to the API with retry logic.
     * @param endpoint The API endpoint
     * @param options The request options
     * @returns A promise that resolves with the response data
     */
    private static async makeRequest<T>(
        endpoint: string,
        options: RequestOptions
    ): Promise<T> {
        let lastError: Error | null = null;
        let delay = this.retryConfig.baseDelay;

        for (let attempt = 0; attempt <= this.retryConfig.maxRetries; attempt++) {
            try {
                const url = `${this.config.baseUrl}${endpoint}`;
                const response = await fetch(url, {
                    ...options,
                    headers: {
                        ...this.config.headers,
                        ...options.headers
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                return response.json();
            } catch (error) {
                lastError = error instanceof Error ? error : new Error('Unknown error');

                if (attempt === this.retryConfig.maxRetries || !shouldRetry(error)) {
                    break;
                }

                await new Promise(resolve => setTimeout(resolve, delay));

                if (this.retryConfig.useExponentialBackoff) {
                    delay = Math.min(delay * 2, this.retryConfig.maxDelay);
                }
            }
        }

        throw lastError;
    }

    /**
     * Validates an API response.
     * @param response The API response to validate
     * @throws AuthServiceError if the response is invalid
     */
    private static validateResponse<T>(response: T): void {
        if (!response || typeof response !== 'object') {
            console.error('Invalid response format:', response);
            throw new AuthServiceError(
                AuthErrorCode.INVALID_RESPONSE,
                'Invalid response format'
            );
        }

        // Check for error response
        const errorResponse = response as any;
        if (errorResponse.error) {
            console.error('API error response:', errorResponse.error);
            throw new AuthServiceError(
                errorResponse.error.code || AuthErrorCode.UNKNOWN_ERROR,
                errorResponse.error.message || 'Unknown error occurred',
                errorResponse.error.status
            );
        }
    }

    /**
     * Handles and transforms errors into AuthServiceErrors.
     * @param error The error to handle
     * @returns An AuthServiceError
     */
    private static handleError(error: unknown): AuthServiceError {
        if (error instanceof AuthServiceError) {
            return error;
        }

        const message = error instanceof Error ? error.message : 'Unknown error';
        return new AuthServiceError(AuthErrorCode.UNKNOWN_ERROR, message);
    }

    async validateAccess(userId: number): Promise<void> {
        const currentUser = await AuthService.getCurrentUser();
        
        if (!currentUser) {
            throw new Error('Authentication required');
        }

        if (currentUser.id !== userId) {
            throw new Error('Unauthorized access');
        }
    }
} 