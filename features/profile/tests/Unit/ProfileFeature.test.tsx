import { ProfileData, ProfileError, ProfileErrorCode } from '../../types/profile';
import { ApiClient } from '../../../../dashboard/services/api';
import { FeatureContext } from '../../../../dashboard/contracts/Feature';
import { ProfileFeature } from '../../ProfileFeature';
import { PROFILE_EVENTS } from '../../events/constants';
import { ApiError, ApiResponse } from '../../../../dashboard/types/api';
import { ProfileEvent } from '../../events/types';
import { mockProfileData } from '../../../../dashboard/testing/mocks/mocks';

// Create a mock factory for ApiClient
const createMockApiClient = () => {
    const mockValidationErrorResponse = {
        data: null,
        error: {
            code: 'validation_error',
            message: 'Invalid profile data',
            status: 400
        }
    } as ApiResponse<{ profile: ProfileData }>;

    const mockNetworkErrorResponse = {
        data: null,
        error: {
            code: 'network_error',
            message: 'Network error occurred',
            status: 500
        }
    } as ApiResponse<{ profile: ProfileData }>;

    const mockSuccessResponse = {
        data: { 
            profile: mockProfileData 
        },
        error: null
    } as ApiResponse<{ profile: ProfileData }>;

    const client = {
        fetch: jest.fn().mockImplementation(async () => mockSuccessResponse),
        post: jest.fn().mockImplementation(async () => mockSuccessResponse),
        fetchWithCache: jest.fn(),
        fetchWithRetry: jest.fn(),
        cache: {},
        context: {},
        isCacheValid: jest.fn(),
        normalizeUrl: jest.fn(),
        // Helper methods for tests to set responses
        setValidationError: function() {
            this.post.mockResolvedValueOnce(mockValidationErrorResponse);
            this.fetch.mockResolvedValueOnce(mockValidationErrorResponse);
            return this;
        },
        setNetworkError: function() {
            this.post.mockResolvedValueOnce(mockNetworkErrorResponse);
            this.fetch.mockResolvedValueOnce(mockNetworkErrorResponse);
            return this;
        }
    };

    return client;
};

// Mock ApiClient
jest.mock('../../../../dashboard/services/api', () => ({
    ApiClient: {
        getInstance: jest.fn(() => createMockApiClient())
    }
}));

describe('Profile Feature', () => {
    let feature: ProfileFeature;
    let mockApiClient: ReturnType<typeof createMockApiClient>;
    let mockDispatchAction: jest.Mock;
    let mockContext: FeatureContext;
    
    beforeEach(() => {
        mockDispatchAction = jest.fn((namespaceOrAction: string | any, action?: any) => {
            // If only one argument is provided, it's the action
            if (!action) {
                return namespaceOrAction;
            }
            // If two arguments are provided, namespace and action
            return action;
        });

        mockApiClient = createMockApiClient();

        // Mock ApiClient.getInstance
        (ApiClient.getInstance as jest.Mock).mockReturnValue(mockApiClient);

        mockContext = {
            apiUrl: 'http://test.com/athlete-dashboard/v1',
            nonce: 'test-nonce',
            debug: true,
            dispatch: jest.fn().mockImplementation((namespace: string) => {
                return (action: any) => {
                    mockDispatchAction(namespace, action);
                    return action;
                };
            })
        } as unknown as FeatureContext;

        feature = new ProfileFeature();
        feature.register(mockContext);
        feature.setState({ isLoading: false, error: null, data: null });
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    describe('Loading State', () => {
        it('should set loading state while fetching profile', async () => {
            const fetchPromise = feature.init();
            expect(feature.getState().isLoading).toBe(true);
            await fetchPromise;
            expect(feature.getState().isLoading).toBe(false);
            expect(feature.getState().data).toEqual(mockProfileData);
            expect(mockContext.dispatch).toHaveBeenCalledWith('athlete-dashboard');
            expect(mockDispatchAction).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.FETCH_REQUEST,
                payload: { userId: 0 }
            });
        });

        it('should handle network errors during fetch', async () => {
            mockApiClient.setNetworkError();

            await feature.init();
            expect(feature.getState().isLoading).toBe(false);
            expect(feature.getState().error).toEqual({
                code: 'NETWORK_ERROR',
                message: 'Network error occurred',
                status: 500
            });
            expect(mockContext.dispatch).toHaveBeenCalledWith('athlete-dashboard');
            expect(mockDispatchAction).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.FETCH_ERROR,
                payload: { error: {
                    code: 'NETWORK_ERROR',
                    message: 'Network error occurred',
                    status: 500
                } }
            });
        });
    });

    describe('Update Profile', () => {
        it('should handle validation errors during update', async () => {
            mockApiClient.setValidationError();

            feature.setState({ isLoading: false, data: mockProfileData });
            const updatePromise = feature.updateProfile({ email: 'invalid' });
            
            expect(feature.getState().isLoading).toBe(true);
            await updatePromise;
            
            expect(feature.getState().isLoading).toBe(false);
            expect(feature.getState().error).toEqual({
                code: 'VALIDATION_ERROR',
                message: 'Invalid profile data',
                status: 400
            });
            expect(mockDispatchAction).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.UPDATE_ERROR,
                payload: { error: {
                    code: 'VALIDATION_ERROR',
                    message: 'Invalid profile data',
                    status: 400
                } }
            });
        });

        it('should reset error state when form is reset', () => {
            const profileError: ProfileError = {
                code: 'VALIDATION_ERROR' as ProfileErrorCode,
                message: 'Invalid profile data'
            };
            feature.setState({ error: profileError, isLoading: false });
            feature.resetForm();
            expect(feature.getState().error).toBeNull();
            expect(feature.getState().isLoading).toBe(false);
            expect(mockDispatchAction).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.FORM_RESET,
                payload: null
            });
        });
    });

    describe('Profile Updates', () => {
        it('should update profile data successfully', async () => {
            const updatedData = {
                ...mockProfileData,
                heightCm: 180,
                weightKg: 75
            };

            const mockResponse = {
                data: { profile: updatedData },
                error: null
            };

            mockApiClient.post.mockResolvedValueOnce(mockResponse);

            feature.setState({ isLoading: false, data: mockProfileData });
            await feature.updateProfile(updatedData);

            expect(feature.getState().isLoading).toBe(false);
            expect(feature.getState().data).toEqual(updatedData);
            expect(feature.getState().error).toBeNull();
            expect(mockContext.dispatch).toHaveBeenCalledWith('athlete-dashboard');
            expect(mockDispatchAction).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.UPDATE_SUCCESS,
                payload: updatedData
            });
        });

        it('should handle validation errors during update', async () => {
            mockApiClient.setValidationError();

            feature.setState({ isLoading: false, data: mockProfileData });
            await feature.updateProfile({ ...mockProfileData, heightCm: -180 });

            expect(feature.getState().isLoading).toBe(false);
            expect(feature.getState().error).toEqual({
                code: 'VALIDATION_ERROR',
                message: 'Invalid profile data',
                status: 400
            });
            expect(mockContext.dispatch).toHaveBeenCalledWith('athlete-dashboard');
            expect(mockDispatchAction).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.UPDATE_ERROR,
                payload: { error: {
                    code: 'VALIDATION_ERROR',
                    message: 'Invalid profile data',
                    status: 400
                } }
            });
        });

        it('should sync with workout feature on profile update', async () => {
            const updatedProfile = {
                ...mockProfileData,
                experienceLevel: 'advanced' as const,
                equipment: ['barbell', 'dumbbell']
            };

            const mockResponse = {
                data: { profile: updatedProfile },
                error: null
            };

            mockApiClient.post.mockResolvedValueOnce(mockResponse);

            feature.setState({ isLoading: false, data: mockProfileData });
            await feature.updateProfile(updatedProfile);

            expect(mockDispatchAction).toHaveBeenCalledWith('athlete-dashboard', {
                type: 'WORKOUT_PREFERENCES_UPDATED',
                payload: {
                    experienceLevel: 'advanced' as const,
                    equipment: ['barbell', 'dumbbell']
                }
            });
        });
    });

    describe('Integration Points', () => {
        it('should sync with workout feature on profile update', async () => {
            const updatedProfile = {
                ...mockProfileData,
                experienceLevel: 'advanced' as const,
                equipment: ['barbell', 'dumbbell']
            };

            const mockResponse = {
                data: { profile: updatedProfile },
                error: null
            };

            mockApiClient.post.mockResolvedValueOnce(mockResponse);

            feature.setState({ isLoading: false, data: mockProfileData });
            await feature.updateProfile(updatedProfile);

            expect(mockDispatchAction).toHaveBeenCalledWith('athlete-dashboard', {
                type: 'WORKOUT_PREFERENCES_UPDATED',
                payload: {
                    experienceLevel: 'advanced' as const,
                    equipment: ['barbell', 'dumbbell']
                }
            });
        });
    });

    describe('Error Handling', () => {
        it('handles validation errors correctly', () => {
            const validationError: ProfileError = {
                code: 'VALIDATION_ERROR' as ProfileErrorCode,
                message: 'Invalid profile data',
                status: 400
            };
            feature.setState({ error: validationError, isLoading: false });
            expect(feature.getState().error).toEqual(validationError);
        });

        it('handles API errors correctly', async () => {
            mockApiClient.setNetworkError();

            await feature.init();
            expect(feature.getState().error).toEqual({
                code: 'NETWORK_ERROR',
                message: 'Network error occurred',
                status: 500
            });
            expect(feature.getState().isLoading).toBe(false);
            expect(mockContext.dispatch).toHaveBeenCalledWith('athlete-dashboard');
            expect(mockDispatchAction).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.FETCH_ERROR,
                payload: { error: {
                    code: 'NETWORK_ERROR',
                    message: 'Network error occurred',
                    status: 500
                } }
            });
        });

        it('resets form state correctly', () => {
            const error: ProfileError = {
                code: 'VALIDATION_ERROR' as ProfileErrorCode,
                message: 'Invalid data',
                status: 400
            };
            feature.setState({ error, isLoading: false });
            feature.resetForm();
            expect(feature.getState().error).toBeNull();
            expect(feature.getState().isLoading).toBe(false);
        });
    });

    describe('Preferences Management', () => {
        it('updates preferences correctly', () => {
            const preferences = {
                heightCm: 185,
                weightKg: 80
            };

            feature.setState({ isLoading: false, data: mockProfileData });
            feature.updatePreferences(preferences);

            expect(feature.getState().data).toEqual({
                ...mockProfileData,
                ...preferences
            });
            expect(mockDispatchAction).toHaveBeenCalledWith('athlete-dashboard', {
                type: ProfileEvent.PREFERENCES_UPDATED,
                payload: {
                    ...mockProfileData,
                    ...preferences
                }
            });
        });

        it('handles preferences update with no profile data', () => {
            const consoleSpy = jest.spyOn(console, 'error');
            feature.setState({ isLoading: false, data: null });
            feature.updatePreferences({ heightCm: 185 });
            
            expect(consoleSpy).toHaveBeenCalledWith('[ProfileFeature] No profile data available');
            expect(mockDispatchAction).not.toHaveBeenCalled();
            consoleSpy.mockRestore();
        });
    });

    describe('Preferences Updates', () => {
        it('should update preferences successfully', () => {
            feature.setState({ isLoading: false, data: mockProfileData });
            feature.updatePreferences({ heightCm: 185 });
            expect(feature.getState().data?.heightCm).toBe(185);
        });

        it('should not update preferences when no profile data exists', () => {
            const consoleSpy = jest.spyOn(console, 'error');
            feature.setState({ isLoading: false, data: null });
            feature.updatePreferences({ heightCm: 185 });
            
            expect(consoleSpy).toHaveBeenCalledWith('[ProfileFeature] No profile data available');
            expect(mockDispatchAction).not.toHaveBeenCalled();
            consoleSpy.mockRestore();
        });

        it('should not update preferences when context is not initialized', () => {
            const consoleSpy = jest.spyOn(console, 'error');
            feature = new ProfileFeature();
            feature.updatePreferences({ heightCm: 185 });
            
            expect(consoleSpy).toHaveBeenCalledWith('[ProfileFeature] Context not initialized');
            expect(mockDispatchAction).not.toHaveBeenCalled();
            consoleSpy.mockRestore();
        });
    });
}); 