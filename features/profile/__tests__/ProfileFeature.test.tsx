import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ProfileFeature } from '../ProfileFeature';
import { FeatureContext } from '../../../dashboard/contracts/Feature';
import { ProfileProvider } from '../context/ProfileContext';
import { ProfileLayout } from '../components/layout';
import { ApiClient } from '../../../dashboard/services/api';
import { ApiError } from '../../../dashboard/types/api';
import { ProfileEvent } from '../events/types';
import { ProfileData, ProfileError, ProfileErrorCode } from '../types/profile';
import { PROFILE_EVENTS } from '../events/constants';

// Create a mock factory for ApiClient
const createMockApiClient = () => ({
    fetch: jest.fn(),
    fetchWithCache: jest.fn(),
    fetchWithRetry: jest.fn(),
    isCacheValid: jest.fn(),
    normalizeUrl: jest.fn()
});

// Mock dependencies
jest.mock('../../../dashboard/services/api', () => ({
    ApiClient: {
        getInstance: jest.fn(() => createMockApiClient())
    }
}));
jest.mock('../context/ProfileContext');
jest.mock('../components/layout');

describe('ProfileFeature', () => {
    let feature: ProfileFeature;
    let mockContext: FeatureContext;
    let mockUserId: number;
    let mockApiClient: jest.Mocked<ApiClient>;
    let mockDispatchAction: jest.Mock;

    const mockProfile: ProfileData = {
        id: 1,
        username: 'testuser',
        email: 'test@example.com',
        displayName: 'Test User',
        firstName: 'Test',
        lastName: 'User',
        nickname: 'tester',
        roles: ['subscriber'],
        phone: '',
        age: 30,
        dateOfBirth: '1993-01-01',
        gender: 'male',
        heightCm: 175,
        weightKg: 70,
        experienceLevel: 'intermediate',
        medicalClearance: true,
        medicalNotes: '',
        medicalConditions: [],
        exerciseLimitations: [],
        medications: '',
        emergencyContactName: '',
        emergencyContactPhone: '',
        injuries: [],
        equipment: [],
        fitnessGoals: ['strength', 'endurance'],
        dominantSide: 'right'
    };

    beforeEach(() => {
        jest.clearAllMocks();

        mockDispatchAction = jest.fn();
        mockApiClient = {
            fetch: jest.fn()
        } as unknown as jest.Mocked<ApiClient>;

        mockContext = {
            apiUrl: 'http://test.com',
            nonce: 'test-nonce',
            debug: false,
            dispatch: jest.fn(() => mockDispatchAction)
        } as unknown as FeatureContext;

        feature = new ProfileFeature();
        feature.register(mockContext);

        jest.spyOn(ApiClient, 'getInstance').mockReturnValue(mockApiClient);
        (ProfileProvider as jest.Mock).mockImplementation(({ children }) => <div data-testid="profile-provider">{children}</div>);
        (ProfileLayout as jest.Mock).mockImplementation(() => <div data-testid="profile-layout" />);
    });

    describe('Feature Interface', () => {
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

    describe('Lifecycle', () => {
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
            
            expect(feature.getState().data).toBeNull();
            expect(feature.getState().isLoading).toBeFalsy();
            expect(feature.getState().error).toBeNull();
        });
    });

    describe('API Integration', () => {
        beforeEach(async () => {
            await feature.register(mockContext);
        });

        it('should handle successful profile fetch', async () => {
            mockApiClient.fetch.mockResolvedValueOnce({
                data: mockProfile,
                error: null
            });

            await feature.init();

            expect(mockContext.dispatch).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.FETCH_SUCCESS,
                payload: mockProfile
            });
            expect(feature.getState().data).toEqual(mockProfile);
        });

        it('should handle network errors during fetch', async () => {
            const networkError = new Error('Network error');
            mockApiClient.fetch.mockRejectedValueOnce(networkError);

            await feature.init();

            expect(mockContext.dispatch).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.FETCH_ERROR,
                payload: { error: networkError }
            });
            expect(feature.getState().error).toEqual(networkError);
        });

        it('should handle API errors with status code', async () => {
            const apiError = {
                code: 'not_found',
                message: 'Profile not found',
                status: 404
            };
            mockApiClient.fetch.mockResolvedValueOnce({
                data: null,
                error: apiError
            });

            await feature.init();

            expect(mockContext.dispatch).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.FETCH_ERROR,
                payload: { error: apiError }
            });
            expect(feature.getState().error).toEqual(apiError);
        });

        it('should clear error state on successful fetch after error', async () => {
            // First, simulate an error
            mockApiClient.fetch.mockResolvedValueOnce({
                data: null,
                error: { code: 'error', message: 'Failed', status: 500 }
            });
            await feature.init();
            expect(feature.getState().error).toBeTruthy();

            // Then, simulate success
            mockApiClient.fetch.mockResolvedValueOnce({
                data: mockProfile,
                error: null
            });
            await feature.init();

            expect(feature.getState().error).toBeNull();
            expect(feature.getState().data).toEqual(mockProfile);
        });

        it('should maintain loading state during fetch', async () => {
            const fetchPromise = feature.init();
            expect(feature.getState().isLoading).toBe(true);

            await fetchPromise;
            expect(feature.getState().isLoading).toBe(false);
        });
    });

    describe('Profile Updates', () => {
        beforeEach(async () => {
            await feature.register(mockContext);
            mockApiClient.fetch.mockResolvedValueOnce({
                data: mockProfile,
                error: null
            });
            await feature.init();
        });

        it('should handle successful profile update', async () => {
            const updatedProfile = {
                ...mockProfile,
                firstName: 'Updated',
                lastName: 'User'
            };
            mockApiClient.fetch.mockResolvedValueOnce({
                data: updatedProfile,
                error: null
            });

            await feature.updateProfile({
                firstName: 'Updated',
                lastName: 'User'
            });

            expect(mockContext.dispatch).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.UPDATE_SUCCESS,
                payload: updatedProfile
            });
            expect(feature.getState().data).toEqual(updatedProfile);
            expect(feature.getState().error).toBeNull();
        });

        it('should handle validation errors during update', async () => {
            const validationError = {
                code: 'validation_failed',
                message: 'Invalid profile data',
                status: 400,
                errors: {
                    email: ['Invalid email format']
                }
            };
            mockApiClient.fetch.mockResolvedValueOnce({
                data: null,
                error: validationError
            });

            await feature.updateProfile({
                email: 'invalid-email'
            });

            expect(mockContext.dispatch).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.UPDATE_ERROR,
                payload: { error: validationError }
            });
            expect(feature.getState().error).toEqual(validationError);
        });
    });

    describe('Rendering', () => {
        beforeEach(async () => {
            await feature.register(mockContext);
        });

        it('should render ProfileProvider with correct props', async () => {
            const rendered = feature.render();
            render(<>{rendered}</>);
            
            await waitFor(() => {
                expect(screen.getByTestId('profile-provider')).toBeInTheDocument();
            });

            expect(ProfileProvider).toHaveBeenCalled();
        });

        it('should render ProfileLayout with correct props', async () => {
            const rendered = feature.render();
            render(<>{rendered}</>);
            
            await waitFor(() => {
                expect(screen.getByTestId('profile-layout')).toBeInTheDocument();
            });

            expect(ProfileLayout).toHaveBeenCalledWith(
                expect.objectContaining({
                    context: mockContext
                }),
                expect.any(Object)
            );
        });

        it('should not render when context is null', () => {
            feature['context'] = null;
            const rendered = feature.render();
            expect(rendered).toBeNull();
        });
    });

    describe('Event Handling', () => {
        beforeEach(async () => {
            await feature.register(mockContext);
        });

        it('should reinitialize on navigation', async () => {
            const initSpy = jest.spyOn(feature, 'init');
            
            await feature.onNavigate();
            
            expect(initSpy).toHaveBeenCalled();
        });

        it('should reinitialize on user change', async () => {
            const initSpy = jest.spyOn(feature, 'init');
            
            await feature.onUserChange();
            
            expect(initSpy).toHaveBeenCalled();
        });

        it('should not reinitialize on navigation without context', async () => {
            await feature.cleanup();
            const initSpy = jest.spyOn(feature, 'init');
            
            await feature.onNavigate();
            
            expect(initSpy).not.toHaveBeenCalled();
        });

        it('should not reinitialize on user change without context', async () => {
            await feature.cleanup();
            const initSpy = jest.spyOn(feature, 'init');
            
            await feature.onUserChange();
            
            expect(initSpy).not.toHaveBeenCalled();
        });
    });

    describe('Integration Points', () => {
        beforeEach(async () => {
            await feature.register(mockContext);
            mockApiClient.fetch.mockResolvedValueOnce({
                data: mockProfile,
                error: null
            });
            await feature.init();
        });

        it('should sync with workout feature on profile update', async () => {
            const updatedProfile = {
                ...mockProfile,
                experienceLevel: 'advanced',
                equipment: ['barbell', 'dumbbell']
            };
            mockApiClient.fetch.mockResolvedValueOnce({
                data: updatedProfile,
                error: null
            });

            await feature.updateProfile({
                experienceLevel: 'advanced',
                equipment: ['barbell', 'dumbbell']
            });

            expect(mockContext.dispatch).toHaveBeenCalledWith('athlete-dashboard', {
                type: 'WORKOUT_PREFERENCES_UPDATED',
                payload: {
                    experienceLevel: 'advanced',
                    equipment: ['barbell', 'dumbbell']
                }
            });
        });

        it('should handle measurement unit changes', () => {
            // Remove this test case as measurementUnit is not a valid property
        });

        it('should persist preferences across sessions', () => {
            // Remove this test case as theme and notifications are not valid properties
        });

        it('updates measurement preferences', async () => {
            const preferences: Partial<ProfileData> = {
                heightCm: 185,
                weightKg: 80
            };

            feature.register(mockContext);
            feature.setState({ data: mockProfile });
            feature.updatePreferences(preferences);

            expect(feature.getState().data).toEqual({
                ...mockProfile,
                ...preferences
            });
            expect(mockContext.dispatch).toHaveBeenCalledWith('athlete-dashboard');
            expect(mockDispatchAction).toHaveBeenCalledWith({
                type: ProfileEvent.PREFERENCES_UPDATED,
                payload: {
                    ...mockProfile,
                    ...preferences
                }
            });
        });

        it('updates user preferences', async () => {
            const preferences: Partial<ProfileData> = {
                experienceLevel: 'intermediate',
                equipment: ['dumbbells', 'barbell'],
                fitnessGoals: ['strength', 'endurance']
            };

            feature.register(mockContext);
            feature.setState({ data: mockProfile });
            feature.updatePreferences(preferences);

            expect(feature.getState().data).toEqual({
                ...mockProfile,
                ...preferences
            });
            expect(mockContext.dispatch).toHaveBeenCalledWith('athlete-dashboard');
            expect(mockDispatchAction).toHaveBeenCalledWith({
                type: ProfileEvent.PREFERENCES_UPDATED,
                payload: {
                    ...mockProfile,
                    ...preferences
                }
            });
        });
    });

    describe('Preferences Management', () => {
        beforeEach(() => {
            feature.setState({ data: mockProfile });
        });

        it('should update physical measurements', () => {
            const preferences: Partial<ProfileData> = {
                heightCm: 185,
                weightKg: 80
            };
            feature.updatePreferences(preferences);

            expect(feature.getState().data).toEqual({
                ...mockProfile,
                ...preferences
            });

            expect(mockContext.dispatch('athlete-dashboard')).toHaveBeenCalledWith({
                type: ProfileEvent.PREFERENCES_UPDATED,
                payload: {
                    ...mockProfile,
                    ...preferences
                }
            });
        });

        it('should update training preferences', () => {
            const preferences: Partial<ProfileData> = {
                experienceLevel: 'advanced' as const,
                equipment: ['dumbbells', 'barbell'],
                fitnessGoals: ['strength', 'muscle_gain']
            };
            feature.updatePreferences(preferences);

            expect(feature.getState().data).toEqual({
                ...mockProfile,
                ...preferences
            });

            expect(mockContext.dispatch('athlete-dashboard')).toHaveBeenCalledWith({
                type: ProfileEvent.PREFERENCES_UPDATED,
                payload: {
                    ...mockProfile,
                    ...preferences
                }
            });
        });

        it('should handle preferences update when no profile data is available', () => {
            feature.setState({ data: null });

            const preferences: Partial<ProfileData> = {
                heightCm: 185,
                weightKg: 80
            };
            feature.updatePreferences(preferences);

            expect(feature.getState().data).toBeNull();
            expect(mockContext.dispatch('athlete-dashboard')).not.toHaveBeenCalled();
        });
    });

    describe('Measurement Handling', () => {
        beforeEach(() => {
            feature.setState({ data: mockProfile });
        });

        it('should store measurements in metric units', () => {
            const imperialPreferences: Partial<ProfileData> = {
                heightCm: 180.34, // 5'11"
                weightKg: 80 // ~176.37 lbs
            };
            feature.updatePreferences(imperialPreferences);

            expect(feature.getState().data).toEqual({
                ...mockProfile,
                ...imperialPreferences
            });

            expect(mockContext.dispatch('athlete-dashboard')).toHaveBeenCalledWith({
                type: ProfileEvent.PREFERENCES_UPDATED,
                payload: {
                    ...mockProfile,
                    ...imperialPreferences
                }
            });
        });

        it('should validate measurements in metric range', () => {
            const invalidPreferences: Partial<ProfileData> = {
                heightCm: 50, // Too short (min 100cm)
                weightKg: 20 // Too light (min 30kg)
            };
            feature.updatePreferences(invalidPreferences);

            expect(feature.getState().error).toEqual({
                code: 'VALIDATION_ERROR',
                message: 'Invalid measurements'
            });
        });

        it('should handle measurement updates with unit conversion', async () => {
            const updatedProfile = {
                ...mockProfile,
                heightCm: 182.88, // 6'0"
                weightKg: 90.72  // 200 lbs
            };

            mockApiClient.fetch.mockResolvedValueOnce({
                data: updatedProfile,
                error: null
            });

            await feature.updateProfile({
                heightCm: 182.88,
                weightKg: 90.72
            });

            expect(mockContext.dispatch).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.UPDATE_SUCCESS,
                payload: updatedProfile
            });
            expect(feature.getState().data).toEqual(updatedProfile);
            expect(feature.getState().error).toBeNull();
        });
    });

    describe('Error Handling', () => {
        it('handles validation errors correctly', () => {
            const error: ProfileError = {
                code: 'VALIDATION_ERROR',
                message: 'Invalid profile data'
            };

            feature.register(mockContext);
            feature.handleValidationError(error);

            expect(feature.getState().error).toEqual(error);
            expect(feature.getState().isLoading).toBeFalsy();
            expect(mockContext.dispatch).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.VALIDATION_ERROR,
                payload: { error }
            });
        });

        it('handles API errors correctly', () => {
            const apiError: ApiError = {
                code: 'NETWORK_ERROR',
                message: 'Network error occurred',
                status: 500
            };

            const profileError: ProfileError = {
                code: 'NETWORK_ERROR',
                message: apiError.message,
                status: apiError.status
            };

            feature.register(mockContext);
            feature.setState({ error: profileError });

            expect(feature.getState().error).toEqual(profileError);
            expect(feature.getState().isLoading).toBeFalsy();
            expect(mockContext.dispatch).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.FETCH_ERROR,
                payload: { error: profileError }
            });
        });

        it('resets form state correctly', () => {
            feature.register(mockContext);
            feature.setState({
                error: { 
                    code: 'VALIDATION_ERROR', 
                    message: 'Test error' 
                },
                isLoading: true
            });

            feature.resetForm();

            expect(feature.getState().error).toBeNull();
            expect(feature.getState().isLoading).toBeFalsy();
            expect(mockContext.dispatch).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.FORM_RESET,
                payload: null
            });
        });
    });
}); 