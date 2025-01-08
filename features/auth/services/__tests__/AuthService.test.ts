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
    // Mock fetch globally
    const mockFetch = jest.fn();
    global.fetch = mockFetch;

    beforeEach(() => {
        // Reset all mocks before each test
        jest.clearAllMocks();
        mockFetch.mockReset();
    });

    describe('login', () => {
        const mockCredentials = {
            username: 'testuser',
            password: 'testpass',
        };

        const mockSuccessResponse = {
            success: true,
            data: {
                token: 'mock-token',
                user: {
                    id: 1,
                    username: 'testuser',
                    email: 'test@example.com',
                },
            },
        };

        it('should successfully login with valid credentials', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve(mockSuccessResponse),
            });

            const response = await AuthService.login(mockCredentials);

            expect(response).toEqual(mockSuccessResponse);
            expect(mockFetch).toHaveBeenCalledTimes(1);
            expect(mockFetch).toHaveBeenCalledWith(
                '/wp-json/athlete-dashboard/v1/auth/login',
                expect.objectContaining({
                    method: 'POST',
                    body: JSON.stringify(mockCredentials),
                })
            );
        });

        it('should handle rate limiting during login', async () => {
            const rateLimiter = RateLimiter.getInstance();
            (rateLimiter.checkRateLimit as jest.Mock).mockImplementationOnce(() => {
                throw new AuthServiceError(
                    'Too many attempts',
                    AuthErrorCode.RATE_LIMIT_EXCEEDED
                );
            });

            await expect(AuthService.login(mockCredentials)).rejects.toThrow(
                AuthServiceError
            );
            expect(rateLimiter.checkRateLimit).toHaveBeenCalledWith(
                `login:${mockCredentials.username}`
            );
        });

        it('should handle network errors with retry logic', async () => {
            mockFetch
                .mockRejectedValueOnce(new Error('Network error'))
                .mockRejectedValueOnce(new Error('Network error'))
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve(mockSuccessResponse),
                });

            const response = await AuthService.login(mockCredentials);

            expect(response).toEqual(mockSuccessResponse);
            expect(mockFetch).toHaveBeenCalledTimes(3);
        });

        it('should handle invalid responses', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve(null),
            });

            await expect(AuthService.login(mockCredentials)).rejects.toThrow(
                'Invalid response format'
            );
        });
    });

    describe('logout', () => {
        it('should successfully logout', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({ success: true }),
            });

            await AuthService.logout();

            expect(mockFetch).toHaveBeenCalledTimes(1);
            expect(mockFetch).toHaveBeenCalledWith(
                '/wp-json/athlete-dashboard/v1/auth/logout',
                expect.objectContaining({
                    method: 'POST',
                })
            );
        });

        it('should handle logout errors', async () => {
            mockFetch.mockRejectedValueOnce(new Error('Logout failed'));

            await expect(AuthService.logout()).rejects.toThrow(AuthServiceError);
        });
    });

    describe('register', () => {
        const mockRegistrationData = {
            username: 'newuser',
            email: 'new@example.com',
            password: 'newpass',
            firstName: 'New',
            lastName: 'User',
        };

        const mockSuccessResponse = {
            success: true,
            data: {
                user: {
                    id: 1,
                    username: 'newuser',
                    email: 'new@example.com',
                },
                token: 'mock-token',
            },
        };

        it('should successfully register a new user', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve(mockSuccessResponse),
            });

            const response = await AuthService.register(mockRegistrationData);

            expect(response).toEqual(mockSuccessResponse);
            expect(mockFetch).toHaveBeenCalledTimes(1);
            expect(mockFetch).toHaveBeenCalledWith(
                '/wp-json/athlete-dashboard/v1/auth/register',
                expect.objectContaining({
                    method: 'POST',
                    body: JSON.stringify(mockRegistrationData),
                })
            );
        });

        it('should handle rate limiting during registration', async () => {
            const rateLimiter = RateLimiter.getInstance();
            (rateLimiter.checkRateLimit as jest.Mock).mockImplementationOnce(() => {
                throw new AuthServiceError(
                    'Too many attempts',
                    AuthErrorCode.RATE_LIMIT_EXCEEDED
                );
            });

            await expect(AuthService.register(mockRegistrationData)).rejects.toThrow(
                AuthServiceError
            );
            expect(rateLimiter.checkRateLimit).toHaveBeenCalledWith(
                `register:${mockRegistrationData.email}`
            );
        });
    });

    describe('checkAuth', () => {
        it('should return true when user is authenticated', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({
                    data: {
                        user: { id: 1, username: 'testuser' },
                    },
                }),
            });

            const isAuthenticated = await AuthService.checkAuth();
            expect(isAuthenticated).toBe(true);
        });

        it('should return false when user is not authenticated', async () => {
            mockFetch.mockRejectedValueOnce(new Error('Not authenticated'));

            const isAuthenticated = await AuthService.checkAuth();
            expect(isAuthenticated).toBe(false);
        });
    });

    describe('refreshToken', () => {
        it('should successfully refresh token', async () => {
            const mockToken = 'new-token';
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({
                    data: { token: mockToken },
                }),
            });

            const token = await AuthService.refreshToken();
            expect(token).toBe(mockToken);
        });

        it('should handle token refresh errors', async () => {
            mockFetch.mockRejectedValueOnce(new Error('Refresh failed'));

            await expect(AuthService.refreshToken()).rejects.toThrow(AuthServiceError);
        });
    });

    describe('error handling', () => {
        it('should handle HTTP errors', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: false,
                status: 401,
                statusText: 'Unauthorized',
            });

            await expect(AuthService.login({
                username: 'test',
                password: 'test',
            })).rejects.toThrow('HTTP error! status: 401');
        });

        it('should handle network errors', async () => {
            mockFetch.mockRejectedValueOnce(new TypeError('Failed to fetch'));

            await expect(AuthService.login({
                username: 'test',
                password: 'test',
            })).rejects.toThrow(AuthServiceError);
        });

        it('should handle invalid JSON responses', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.reject(new Error('Invalid JSON')),
            });

            await expect(AuthService.login({
                username: 'test',
                password: 'test',
            })).rejects.toThrow(AuthServiceError);
        });
    });
}); 