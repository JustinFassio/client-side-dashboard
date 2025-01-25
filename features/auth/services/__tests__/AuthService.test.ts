import { AuthService } from '../AuthService';
import { AuthServiceError } from '../errors';
import { RateLimiter } from '../rateLimiting';
import { AuthErrorCode } from '../../types';

// Mock the EventEmitter
jest.mock('../../../../dashboard/events', () => ({
    EventEmitter: {
        emit: jest.fn(),
    },
}));

// Mock the RateLimiter
jest.mock('../rateLimiting', () => ({
    RateLimiter: {
        getInstance: jest.fn(() => ({
            checkRateLimit: jest.fn(),
            incrementAttempts: jest.fn(),
            resetState: jest.fn(),
        })),
    },
}));

describe('AuthService', () => {
    let mockFetch: jest.Mock;

    const mockCredentials = {
        username: 'testuser',
        password: 'password123'
    };

    beforeEach(() => {
        mockFetch = jest.fn();
        global.fetch = mockFetch;
        jest.clearAllMocks();
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    describe('login', () => {
        it('successfully logs in user', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({ token: 'mock-token' })
            });

            const response = await AuthService.login(mockCredentials);
            expect(response).toBeDefined();
            expect(mockFetch).toHaveBeenCalledWith(
                expect.stringContaining('/auth/login'),
                expect.any(Object)
            );
        });

        it('handles rate limit exceeded', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: false,
                status: 429,
                json: () => Promise.resolve({ 
                    error: AuthErrorCode.RATE_LIMIT_EXCEEDED,
                    message: 'Too many attempts'
                })
            });

            await expect(AuthService.login(mockCredentials))
                .rejects
                .toThrow(new AuthServiceError(AuthErrorCode.RATE_LIMIT_EXCEEDED, 'Too many attempts'));
        });

        it('handles invalid credentials', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: false,
                status: 401,
                json: () => Promise.resolve({ 
                    error: AuthErrorCode.INVALID_CREDENTIALS,
                    message: 'Invalid username or password'
                })
            });

            await expect(AuthService.login(mockCredentials))
                .rejects
                .toThrow(new AuthServiceError(AuthErrorCode.INVALID_CREDENTIALS, 'Invalid username or password'));
        });
    });

    describe('logout', () => {
        it('successfully logs out user', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({ success: true })
            });

            await AuthService.logout();
            expect(mockFetch).toHaveBeenCalledWith(
                expect.stringContaining('/auth/logout'),
                expect.any(Object)
            );
        });

        it('handles logout failure', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: false,
                status: 500,
                json: () => Promise.resolve({ 
                    error: AuthErrorCode.UNKNOWN_ERROR,
                    message: 'Server error during logout'
                })
            });

            await expect(AuthService.logout())
                .rejects
                .toThrow(new AuthServiceError(AuthErrorCode.UNKNOWN_ERROR, 'Server error during logout'));
        });
    });

    describe('register', () => {
        const mockRegistrationData = {
            username: 'testuser',
            email: 'test@example.com',
            password: 'Password123!',
            firstName: 'Test',
            lastName: 'User'
        };

        it('should register a new user successfully', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({ success: true, user: { id: 1 }, token: 'token' })
            });

            const response = await AuthService.register(mockRegistrationData);
            expect(response).toBeDefined();
            expect(response.success).toBe(true);
        });

        it('should handle network errors', async () => {
            mockFetch.mockRejectedValueOnce(new Error('Network error'));
            
            await expect(AuthService.register(mockRegistrationData))
                .rejects
                .toThrow(new AuthServiceError(AuthErrorCode.NETWORK_ERROR, 'Network error'));
        });

        it('should handle registration failure', async () => {
            mockFetch.mockRejectedValueOnce(new Error('Registration failed'));
            
            await expect(AuthService.register(mockRegistrationData))
                .rejects
                .toThrow(new AuthServiceError(AuthErrorCode.REGISTRATION_FAILED, 'Registration failed'));
        });

        it('should handle invalid response', async () => {
            mockFetch.mockRejectedValueOnce(new Error('Invalid response'));
            
            await expect(AuthService.register(mockRegistrationData))
                .rejects
                .toThrow(new AuthServiceError(AuthErrorCode.INVALID_RESPONSE, 'Invalid response'));
        });
    });

    describe('checkAuth', () => {
        it('confirms valid authentication', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({ isAuthenticated: true })
            });

            const response = await AuthService.checkAuth();
            expect(response).toBe(true);
        });

        it('handles expired token', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: false,
                status: 401,
                json: () => Promise.resolve({ 
                    error: AuthErrorCode.SESSION_EXPIRED,
                    message: 'Token has expired'
                })
            });

            await expect(AuthService.checkAuth())
                .rejects
                .toThrow(new AuthServiceError(AuthErrorCode.SESSION_EXPIRED, 'Token has expired'));
        });
    });

    describe('refreshToken', () => {
        it('successfully refreshes token', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({ token: 'new-token' })
            });

            const response = await AuthService.refreshToken();
            expect(response).toBeDefined();
            expect(mockFetch).toHaveBeenCalledWith(
                expect.stringContaining('/auth/refresh'),
                expect.any(Object)
            );
        });

        it('handles refresh token failure', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: false,
                status: 401,
                json: () => Promise.resolve({ 
                    error: AuthErrorCode.TOKEN_REFRESH_FAILED,
                    message: 'Invalid refresh token'
                })
            });

            await expect(AuthService.refreshToken())
                .rejects
                .toThrow(new AuthServiceError(AuthErrorCode.TOKEN_REFRESH_FAILED, 'Invalid refresh token'));
        });
    });

    describe('error handling', () => {
        it('should handle HTTP errors', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: false,
                status: 401,
                statusText: 'Unauthorized',
            });

            await expect(AuthService.login(mockCredentials))
                .rejects
                .toThrow('HTTP error! status: 401');
        });

        it('should handle network errors', async () => {
            mockFetch.mockRejectedValueOnce(new TypeError('Failed to fetch'));

            await expect(AuthService.login(mockCredentials))
                .rejects
                .toThrow(AuthServiceError);
        });

        it('should handle invalid JSON responses', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.reject(new Error('Invalid JSON')),
            });

            await expect(AuthService.login(mockCredentials))
                .rejects
                .toThrow(AuthServiceError);
        });
    });

    describe('rate limiting', () => {
        it('throws rate limit error when too many attempts', async () => {
            const maxAttempts = 5;
            for (let i = 0; i < maxAttempts; i++) {
                await AuthService.login(mockCredentials);
            }

            await expect(AuthService.login(mockCredentials))
                .rejects
                .toThrow(new AuthServiceError(
                    AuthErrorCode.RATE_LIMIT_EXCEEDED,
                    'Too many attempts'
                ));
        });

        it('resets rate limit after window expires', async () => {
            const maxAttempts = 5;
            for (let i = 0; i < maxAttempts; i++) {
                await AuthService.login(mockCredentials);
            }

            // Fast forward time by rate limit window
            jest.advanceTimersByTime(3600 * 1000);

            // Should not throw rate limit error
            await expect(AuthService.login(mockCredentials))
                .resolves
                .not
                .toThrow();
        });
    });
}); 