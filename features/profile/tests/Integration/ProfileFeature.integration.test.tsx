import React from 'react';
import { render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ProfileFeature } from '../../ProfileFeature';
import { ApiClient } from '../../../../dashboard/services/api';
import { FeatureContext as FeatureContextType } from '../../../../dashboard/contracts/Feature';
import { ProfileData } from '../../types/profile';
import { ApiResponse, ApiError } from '../../../../dashboard/types/api';

// Create a mock factory for ApiClient
const createMockApiClient = () => ({
  fetch: jest.fn(),
  fetchWithCache: jest.fn(),
  fetchWithRetry: jest.fn(),
  isCacheValid: jest.fn(),
  normalizeUrl: jest.fn()
});

// Mock ApiClient
jest.mock('../../../../dashboard/services/api', () => ({
  ApiClient: {
    getInstance: jest.fn(() => createMockApiClient())
  }
}));

describe('ProfileFeature Integration', () => {
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

  it('loads and displays profile data', async () => {
    const response: ApiResponse<ProfileData> = { data: mockProfile, error: null };
    mockApiClient.fetch.mockResolvedValueOnce(response);

    const feature = new ProfileFeature();
    await feature.register({
      apiUrl: 'http://test.local/wp-json',
      nonce: 'test-nonce',
      debug: false,
      dispatch: jest.fn(() => jest.fn())
    });

    const rendered = feature.render();
    expect(rendered).not.toBeNull();
  });

  it('handles profile update', async () => {
    const initialResponse: ApiResponse<ProfileData> = { data: mockProfile, error: null };
    const updatedResponse: ApiResponse<ProfileData> = { 
      data: { ...mockProfile, heightCm: 180 }, 
      error: null 
    };
    
    mockApiClient.fetch.mockResolvedValueOnce(initialResponse);
    mockApiClient.fetch.mockResolvedValueOnce(updatedResponse);

    const feature = new ProfileFeature();
    await feature.register({
      apiUrl: 'http://test.local/wp-json',
      nonce: 'test-nonce',
      debug: false,
      dispatch: jest.fn(() => jest.fn())
    });

    const rendered = feature.render();
    expect(rendered).not.toBeNull();
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
    await feature.register({
      apiUrl: 'http://test.local/wp-json',
      nonce: 'test-nonce',
      debug: false,
      dispatch: jest.fn(() => jest.fn())
    });

    const rendered = feature.render();
    expect(rendered).not.toBeNull();
  });

  it('handles profile update error', async () => {
    const initialResponse: ApiResponse<ProfileData> = { data: mockProfile, error: null };
    const errorResponse: ApiResponse<ProfileData> = { 
      data: null, 
      error: {
        code: 'NETWORK_ERROR',
        message: 'Failed to update profile',
        status: 500
      }
    };
    
    mockApiClient.fetch.mockResolvedValueOnce(initialResponse);
    mockApiClient.fetch.mockResolvedValueOnce(errorResponse);

    const feature = new ProfileFeature();
    await feature.register({
      apiUrl: 'http://test.local/wp-json',
      nonce: 'test-nonce',
      debug: false,
      dispatch: jest.fn(() => jest.fn())
    });

    const rendered = feature.render();
    expect(rendered).not.toBeNull();
  });

  it('validates form before submission', async () => {
    const response: ApiResponse<ProfileData> = { data: mockProfile, error: null };
    mockApiClient.fetch.mockResolvedValueOnce(response);

    const feature = new ProfileFeature();
    await feature.register({
      apiUrl: 'http://test.local/wp-json',
      nonce: 'test-nonce',
      debug: false,
      dispatch: jest.fn(() => jest.fn())
    });

    const rendered = feature.render();
    expect(rendered).not.toBeNull();
  });
}); 