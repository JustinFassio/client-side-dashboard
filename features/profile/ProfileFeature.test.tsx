import { Feature, FeatureContext } from '../../dashboard/contracts/Feature';
import { ProfileEvent } from './events';
import { ProfileProvider } from './context/ProfileContext';
import { ProfileLayout } from './components/layout';
import { UserProvider } from '../user/context/UserContext';
import { ProfileData, ProfileState } from './types/profile';
import { ProfileService } from './services/ProfileService';
import { ApiClient } from '../../dashboard/services/api';
import { ApiError } from '../../dashboard/types/api';
import { ProfileFeature } from './ProfileFeature';
import { mockProfileData } from '../../dashboard/testing/mocks/mocks';

const mockProfile: ProfileData = {
    id: 1,
    username: 'testuser',
    email: 'test@example.com',
    displayName: 'Test User',
    firstName: 'Test',
    lastName: 'User',
    nickname: 'tester',
    roles: ['subscriber'],
    heightCm: 180,
    weightKg: 75,
    experienceLevel: 'intermediate',
    medicalConditions: [],
    exerciseLimitations: [],
    medications: '',
    medicalClearance: false,
    medicalNotes: '',
    phone: '123-456-7890',
    age: 25,
    dateOfBirth: '1998-01-01',
    gender: 'male',
    dominantSide: 'right',
    emergencyContactName: '',
    emergencyContactPhone: '',
    injuries: [],
    equipment: [],
    fitnessGoals: ['strength', 'endurance']
};

interface ApiResponse<T> {
    success: boolean;
    data: T | null;
    error: ApiError | null;
}

interface ProfileResponse {
    id: number;
    profile: ProfileData;
}

const mockApiClient = {
    fetch: jest.fn<Promise<ApiResponse<ProfileResponse>>, [string]>(),
    post: jest.fn<Promise<ApiResponse<ProfileResponse>>, [string, any]>()
};

jest.mock('../../dashboard/services/api', () => ({
    ApiClient: {
        getInstance: () => mockApiClient
    }
}));

describe('Profile Feature', () => {
    let feature: ProfileFeature;
    let mockDispatchAction: jest.Mock;

    beforeEach(() => {
        mockDispatchAction = jest.fn();
        feature = new ProfileFeature();
        feature.register({
            apiUrl: 'http://test.local/wp-json',
            nonce: 'test-nonce',
            debug: false,
            dispatch: () => mockDispatchAction
        });
        jest.clearAllMocks();
    });

    describe('Preferences Management', () => {
        it('should update measurement preferences', () => {
            // Set initial state with profile data
            feature.setState({ data: mockProfileData });

            // Update measurement preferences
            const preferences = {
                heightCm: 185,
                weightKg: 80
            };
            feature.updatePreferences(preferences);

            // Verify state is updated
            expect(feature.getState().data).toEqual({
                ...mockProfileData,
                ...preferences
            });

            // Verify dispatch is called with correct event
            expect(mockDispatchAction).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.PREFERENCES_UPDATED,
                payload: {
                    ...mockProfileData,
                    ...preferences
                }
            });
        });

        it('should update user preferences', () => {
            // Set initial state with profile data
            feature.setState({ data: mockProfileData });

            // Update user preferences
            const preferences = {
                experienceLevel: 'advanced' as const,
                equipment: ['dumbbells', 'barbell'],
                fitnessGoals: ['strength', 'muscle_gain']
            };
            feature.updatePreferences(preferences);

            // Verify state is updated
            expect(feature.getState().data).toEqual({
                ...mockProfileData,
                ...preferences
            });

            // Verify dispatch is called with correct event
            expect(mockDispatchAction).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.PREFERENCES_UPDATED,
                payload: {
                    ...mockProfileData,
                    ...preferences
                }
            });
        });

        it('should handle preferences update when no profile data is available', () => {
            // Ensure no profile data is set
            feature.setState({ data: null });

            // Try to update preferences
            const preferences = {
                heightCm: 185,
                weightKg: 80
            };
            feature.updatePreferences(preferences);

            // Verify state remains unchanged
            expect(feature.getState().data).toBeNull();

            // Verify dispatch is not called
            expect(mockDispatchAction).not.toHaveBeenCalled();
        });
    });

    describe('Error Handling', () => {
        it('handles validation errors correctly', () => {
            const validationError: ProfileError = {
                code: 'VALIDATION_ERROR',
                message: 'Invalid profile data'
            };
            feature.setState({ error: null, isLoading: false });
            feature.handleValidationError(validationError);
            expect(feature.getState().error).toEqual(validationError);
            expect(feature.getState().isLoading).toBe(false);
            expect(mockDispatchAction).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.VALIDATION_ERROR,
                payload: { error: validationError }
            });
        });

        it('handles network errors correctly', async () => {
            const networkError: ProfileError = {
                code: 'NETWORK_ERROR',
                message: 'Failed to fetch profile',
                status: 500
            };
            mockApiClient.fetch.mockRejectedValueOnce(networkError);
            feature.setState({ error: null, isLoading: true });
            await feature.init();
            expect(feature.getState().error).toEqual(networkError);
            expect(feature.getState().isLoading).toBe(false);
            expect(mockDispatchAction).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.FETCH_ERROR,
                payload: { error: networkError }
            });
        });

        it('resets form state correctly', () => {
            const testError: ProfileError = {
                code: 'VALIDATION_ERROR',
                message: 'Test error'
            };
            feature.setState({ error: testError, isLoading: false });
            feature.resetForm();
            expect(feature.getState().error).toBeNull();
            expect(feature.getState().isLoading).toBe(false);
            expect(mockDispatchAction).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.FORM_RESET,
                payload: null
            });
        });
    });

    describe('Loading State', () => {
        it('should set loading state while fetching profile', async () => {
            const fetchPromise = feature.init();
            expect(feature.getState().isLoading).toBe(true);
            
            await fetchPromise;
            expect(feature.getState().isLoading).toBe(false);
            expect(feature.getState().data).toEqual(mockProfileData);
        });

        it('should handle network errors during fetch', async () => {
            const apiError = {
                code: 'NETWORK_ERROR',
                message: 'Failed to fetch profile',
                status: 500
            };

            mockApiClient.fetch.mockResolvedValueOnce({
                data: null,
                error: apiError
            });

            await feature.init();
            expect(feature.getState().isLoading).toBe(false);
            expect(feature.getState().error).toEqual(apiError);
        });
    });

    describe('Profile Updates', () => {
        it('should handle successful profile update', async () => {
            const updatedProfile = {
                ...mockProfileData,
                firstName: 'Updated'
            };

            const mockResponse: ApiResponse<ProfileResponse> = {
                success: true,
                data: {
                    id: mockProfileData.id,
                    profile: updatedProfile
                },
                error: null
            };

            mockApiClient.fetch.mockResolvedValueOnce(mockResponse);

            feature.setState({ isLoading: false, data: mockProfileData });
            const updatePromise = feature.updateProfile({ firstName: 'Updated' });
            
            expect(feature.getState().isLoading).toBe(true);
            await updatePromise;
            
            expect(feature.getState().isLoading).toBe(false);
            expect(feature.getState().data).toEqual(updatedProfile);
            expect(mockDispatchAction).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.UPDATE_SUCCESS,
                payload: updatedProfile
            });
        });

        it('should handle validation errors during update', async () => {
            const validationError: ApiError = {
                code: 'VALIDATION_ERROR',
                message: 'Invalid profile data',
                errors: {
                    email: ['Invalid email format']
                },
                status: 400
            };

            const mockResponse: ApiResponse<ProfileResponse> = {
                success: false,
                data: null,
                error: validationError
            };

            mockApiClient.fetch.mockResolvedValueOnce(mockResponse);

            feature.setState({ isLoading: false, data: mockProfileData });
            const updatePromise = feature.updateProfile({ email: 'invalid' });
            
            expect(feature.getState().isLoading).toBe(true);
            await updatePromise;
            
            expect(feature.getState().isLoading).toBe(false);
            expect(feature.getState().error).toEqual(validationError);
            expect(mockDispatchAction).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.UPDATE_ERROR,
                payload: { error: validationError }
            });
        });

        it('should handle network errors', async () => {
            const networkError: ApiError = {
                code: 'NETWORK_ERROR',
                message: 'Failed to connect to server',
                status: 500
            };

            const mockResponse: ApiResponse<ProfileResponse> = {
                success: false,
                data: null,
                error: networkError
            };

            mockApiClient.fetch.mockResolvedValueOnce(mockResponse);

            feature.setState({ isLoading: false, data: mockProfileData });
            const updatePromise = feature.updateProfile({ firstName: 'Updated' });
            
            expect(feature.getState().isLoading).toBe(true);
            await updatePromise;
            
            expect(feature.getState().isLoading).toBe(false);
            expect(feature.getState().error).toEqual(networkError);
            expect(mockDispatchAction).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.UPDATE_ERROR,
                payload: { error: networkError }
            });
        });
    });
}); 