import React from 'react';
import { render, screen } from '@testing-library/react';
import { ProfileFeature } from '../ProfileFeature';
import { Feature, FeatureContext } from '../../../dashboard/contracts/Feature';
import { ProfileEvent } from '../events';
import { ApiClient } from '../../../dashboard/services/api';
import { API_ROUTES } from '../../../dashboard/constants/api';
import { ProfileProvider } from '../context/ProfileContext';
import { ProfileLayout } from '../components/layout';

// Mock dependencies
jest.mock('../../../dashboard/services/api');
jest.mock('../context/ProfileContext');
jest.mock('../components/layout');

describe('ProfileFeature', () => {
    let feature: ProfileFeature;
    let mockContext: FeatureContext;
    let mockUserId: number;

    beforeEach(() => {
        // Reset all mocks
        jest.clearAllMocks();

        // Mock context
        mockContext = {
            debug: false,
            dispatch: jest.fn(),
            apiUrl: 'http://test.com/api',
            nonce: 'test-nonce',
            userId: 1
        };

        mockUserId = 1;
        feature = new ProfileFeature();

        // Mock ProfileProvider and ProfileLayout
        (ProfileProvider as jest.Mock).mockImplementation(({ children }) => <div data-testid="profile-provider">{children}</div>);
        (ProfileLayout as jest.Mock).mockImplementation(() => <div data-testid="profile-layout" />);
    });

    describe('Feature Interface Implementation', () => {
        it('should have correct identifier and metadata', () => {
            expect(feature.identifier).toBe('profile');
            expect(feature.metadata).toEqual({
                name: 'Profile',
                description: 'Personalize your journey',
                order: 1
            });
        });

        it('should be enabled by default', () => {
            expect(feature.isEnabled()).toBe(true);
        });
    });

    describe('Lifecycle Methods', () => {
        it('should register with context', async () => {
            await feature.register(mockContext);
            expect(feature['context']).toBe(mockContext);
        });

        it('should log registration in debug mode', async () => {
            const debugContext = { ...mockContext, debug: true };
            const consoleSpy = jest.spyOn(console, 'log');
            
            await feature.register(debugContext);
            
            expect(consoleSpy).toHaveBeenCalledWith('Profile feature registered');
        });

        it('should cleanup properly', async () => {
            await feature.register(mockContext);
            await feature.cleanup();
            
            expect(feature['context']).toBeNull();
            expect(feature['profile']).toBeNull();
        });
    });

    describe('API Integration', () => {
        const mockUserData = {
            id: 1,
            name: 'Test User',
            email: 'test@example.com'
        };

        beforeEach(async () => {
            await feature.register(mockContext);
        });

        it('should handle successful profile fetch', async () => {
            // Mock successful API response
            (ApiClient.getInstance as jest.Mock).mockReturnValue({
                fetchWithCache: jest.fn().mockResolvedValue({
                    data: mockUserData,
                    error: null
                })
            });

            await feature.init();

            expect(mockContext.dispatch).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.FETCH_SUCCESS,
                payload: mockUserData
            });
        });

        it('should handle fetch errors', async () => {
            const mockError = new Error('API Error');
            
            // Mock API error response
            (ApiClient.getInstance as jest.Mock).mockReturnValue({
                fetchWithCache: jest.fn().mockResolvedValue({
                    data: null,
                    error: mockError
                })
            });

            await feature.init();

            expect(mockContext.dispatch).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.FETCH_ERROR,
                payload: { error: mockError.message }
            });
        });

        it('should not fetch if context is null', async () => {
            await feature.cleanup();
            const apiSpy = jest.spyOn(ApiClient, 'getInstance');
            
            await feature.init();
            
            expect(apiSpy).not.toHaveBeenCalled();
        });
    });

    describe('Rendering', () => {
        beforeEach(async () => {
            await feature.register(mockContext);
        });

        it('should render ProfileProvider with correct props', () => {
            const rendered = feature.render({ userId: mockUserId });
            const { container } = render(<>{rendered}</>);
            
            expect(screen.getByTestId('profile-provider')).toBeInTheDocument();
            expect(ProfileProvider).toHaveBeenCalledWith(
                expect.objectContaining({ userId: mockUserId }),
                expect.any(Object)
            );
        });

        it('should render ProfileLayout with correct props', () => {
            const rendered = feature.render({ userId: mockUserId });
            render(<>{rendered}</>);
            
            expect(screen.getByTestId('profile-layout')).toBeInTheDocument();
            expect(ProfileLayout).toHaveBeenCalledWith(
                expect.objectContaining({
                    userId: mockUserId,
                    context: mockContext
                }),
                expect.any(Object)
            );
        });

        it('should not render when context is null', () => {
            feature['context'] = null;
            const rendered = feature.render({ userId: mockUserId });
            expect(rendered).toBeNull();
        });
    });

    describe('Event Handling', () => {
        beforeEach(async () => {
            await feature.register(mockContext);
        });

        it('should reinitialize on navigation', async () => {
            const initSpy = jest.spyOn(feature, 'init');
            
            feature.onNavigate();
            
            expect(initSpy).toHaveBeenCalled();
        });

        it('should reinitialize on user change', async () => {
            const initSpy = jest.spyOn(feature, 'init');
            
            feature.onUserChange();
            
            expect(initSpy).toHaveBeenCalled();
        });

        it('should not reinitialize on navigation without context', async () => {
            await feature.cleanup();
            const initSpy = jest.spyOn(feature, 'init');
            
            feature.onNavigate();
            
            expect(initSpy).not.toHaveBeenCalled();
        });

        it('should not reinitialize on user change without context', async () => {
            await feature.cleanup();
            const initSpy = jest.spyOn(feature, 'init');
            
            feature.onUserChange();
            
            expect(initSpy).not.toHaveBeenCalled();
        });
    });
}); 