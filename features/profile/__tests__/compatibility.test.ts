import { ProfileFeature } from '../ProfileFeature';
import { ApiClient } from '../../../dashboard/services/api';
import { ProfileData, ProfileError } from '../types/profile';
import { FeatureContext } from '../../../dashboard/contracts/Feature';
import { ApiResponse, ApiError } from '../../../dashboard/types/api';

// Create a mock factory for ApiClient
const createMockApiClient = () => ({
  fetch: jest.fn(),
  fetchWithCache: jest.fn(),
  fetchWithRetry: jest.fn(),
  isCacheValid: jest.fn(),
  normalizeUrl: jest.fn()
});

// Mock ApiClient
jest.mock('../../../dashboard/services/api', () => ({
  ApiClient: {
    getInstance: jest.fn(() => createMockApiClient())
  }
}));

describe('Profile Feature Compatibility', () => {
  let mockApiClient: ReturnType<typeof createMockApiClient>;

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
    mockApiClient = createMockApiClient();
    (ApiClient.getInstance as jest.Mock).mockReturnValue(mockApiClient);
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  it('initializes with default state', () => {
    const feature = new ProfileFeature();
    const state = feature.getState();

    expect(state.data).toBeNull();
    expect(state.isLoading).toBeFalsy();
    expect(state.error).toBeNull();
    expect(state.isComplete).toBeFalsy();
  });

  it('loads profile data on mount', async () => {
    const response: ApiResponse<ProfileData> = { data: mockProfile, error: null };
    mockApiClient.fetch.mockResolvedValueOnce(response);

    const feature = new ProfileFeature();
    await feature.init();

    expect(mockApiClient.fetch).toHaveBeenCalledWith('/profile/1');
    expect(feature.getState().data).toEqual(mockProfile);
    expect(feature.getState().isLoading).toBeFalsy();
  });

  it('handles profile load error', async () => {
    const error: ApiError = {
      code: 'NETWORK_ERROR',
      message: 'Failed to load profile',
      status: 500
    };
    const errorResponse: ApiResponse<ProfileData> = { data: null, error };
    mockApiClient.fetch.mockResolvedValueOnce(errorResponse);

    const feature = new ProfileFeature();
    await feature.init();

    expect(feature.getState().error).toEqual(error);
    expect(feature.getState().isLoading).toBeFalsy();
    expect(feature.getState().data).toBeNull();
  });

  it('updates profile data', async () => {
    const updatedProfile = { ...mockProfile, heightCm: 180 };
    const response: ApiResponse<ProfileData> = { data: updatedProfile, error: null };
    mockApiClient.fetch.mockResolvedValueOnce(response);

    const feature = new ProfileFeature();
    feature.setState({ data: mockProfile });

    await feature.updateProfile({ heightCm: 180 });

    expect(mockApiClient.fetch).toHaveBeenCalledWith('/profile/1', expect.objectContaining({
      method: 'PUT',
      body: JSON.stringify({ heightCm: 180 })
    }));
    expect(feature.getState().data).toEqual(updatedProfile);
  });

  it('handles profile update error', async () => {
    const error: ApiError = {
      code: 'NETWORK_ERROR',
      message: 'Failed to update profile',
      status: 500
    };
    const errorResponse: ApiResponse<ProfileData> = { data: null, error };
    mockApiClient.fetch.mockResolvedValueOnce(errorResponse);

    const feature = new ProfileFeature();
    feature.setState({ data: mockProfile });

    await feature.updateProfile({ heightCm: 180 });

    expect(feature.getState().error).toEqual(error);
    expect(feature.getState().data).toEqual(mockProfile); // Original profile should remain unchanged
  });

  it('cleans up on unmount', async () => {
    const feature = new ProfileFeature();
    await feature.cleanup();

    expect(feature.getState().data).toBeNull();
    expect(feature.getState().isLoading).toBeFalsy();
    expect(feature.getState().error).toBeNull();
    expect(feature.getState().isComplete).toBeFalsy();
  });
}); 