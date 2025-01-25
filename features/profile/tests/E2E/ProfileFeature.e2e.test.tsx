import React from 'react';
import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import { ProfileFeature } from '../../ProfileFeature';
import type { ProfileData } from '../../types/profile';
import { FeatureContext } from '../../../../dashboard/contracts/Feature';
import { ApiClient } from '../../../../dashboard/services/api';
import { UserProvider } from '../../../user/context/UserContext';
import { ProfileProvider } from '../../../profile/context/ProfileContext';
import { ProfileService } from '../../services/ProfileService';
import userEvent from '@testing-library/user-event';

// Mock profile data
const mockProfileData = {
    user_id: 3,
    data: {
        basic: {
            id: 3,
            username: 'testuser',
            email: 'test@example.com',
            displayName: 'Test User',
            firstName: 'Test',
            lastName: 'User',
            nickname: 'testuser',
            phone: '123-456-7890'
        },
        medical: {
            conditions: [],
            medications: '',
            allergies: [],
            medicalClearance: true,
            medicalNotes: ''
        },
        account: {
            roles: ['subscriber']
        },
        physical: {
            heightCm: 175,
            weightKg: 70,
            experienceLevel: 'intermediate',
            age: 30,
            dateOfBirth: '1990-01-01',
            gender: 'other',
            dominantSide: 'right'
        },
        injuries: []
    }
};

// Mock API client
const mockApiClient = {
    fetch: jest.fn<Promise<any>, [string]>(),
    post: jest.fn<Promise<any>, [string, any]>(),
    getInstance: () => mockApiClient,
    cache: new Map(),
    context: {},
    rateLimitDelay: 0,
    isCacheValid: () => true,
    clearCache: () => {},
    setContext: () => {},
    getContext: () => ({}),
    setRateLimitDelay: () => {}
} as unknown as ApiClient;

// Mock UserContext
jest.mock('../../../user/context/UserContext', () => ({
    UserProvider: ({ children }: { children: React.ReactNode }) => <>{children}</>,
    useUser: () => ({
        user: { ID: 3, display_name: 'Test User' },
        isLoading: false,
        error: null,
        isAuthenticated: true
    })
}));

jest.mock('../../../../dashboard/services/api', () => ({
    ApiClient: {
        getInstance: jest.fn(() => mockApiClient)
    }
}));

const mockContext = {
    apiUrl: 'http://aiworkoutgenerator-local.local/wp-json',
    nonce: 'test-nonce',
    debug: true,
    dispatch: jest.fn()
} as FeatureContext;

describe('ProfileFeature E2E', () => {
    let feature: ProfileFeature;
    let profileService: ProfileService;

    beforeEach(() => {
        jest.clearAllMocks();
        feature = new ProfileFeature();
        profileService = new ProfileService(mockApiClient, mockContext.nonce);

        // Mock successful fetch response
        (mockApiClient.fetch as jest.Mock).mockImplementation((endpoint: string) => {
            if (endpoint.includes('/profile/user/')) {
                return Promise.resolve({
                    success: true,
                    data: mockProfileData,
                    error: null
                });
            }
            return Promise.reject({
                success: false,
                data: null,
                error: {
                    code: 'not_found',
                    message: 'Not Found',
                    status: 404
                }
            });
        });

        // Mock successful post response
        (mockApiClient.post as jest.Mock).mockImplementation((endpoint: string, data: any) => {
            if (endpoint.includes('/profile/user/')) {
                return Promise.resolve({
                    success: true,
                    data: {
                        ...mockProfileData,
                        ...data
                    },
                    error: null
                });
            }
            return Promise.reject({
                success: false,
                data: null,
                error: {
                    code: 'bad_request',
                    message: 'Bad Request',
                    status: 400
                }
            });
        });
    });

    it('should render profile data successfully', async () => {
        // Register and initialize the feature
        await feature.register(mockContext);
        await feature.init();

        // Get the rendered element
        const element = feature.render({ userId: 3 });
        render(
            <UserProvider>
                <ProfileProvider api={mockApiClient} userId={3}>
                    {element}
                </ProfileProvider>
            </UserProvider>
        );
        
        // Wait for profile data to load
        await waitFor(() => {
            expect(screen.getByText('Test User')).toBeInTheDocument();
        });

        // Verify API call was made
        expect(mockApiClient.fetch).toHaveBeenCalledWith('/profile/user/3');
    });

    it('should handle profile updates', async () => {
        const updatedProfile = {
            ...mockProfileData,
            data: {
                ...mockProfileData.data,
                basic: {
                    ...mockProfileData.data.basic,
                    displayName: 'Updated Name'
                }
            }
        };
        (mockApiClient.post as jest.Mock).mockResolvedValueOnce({ success: true, data: updatedProfile });
        
        await userEvent.type(screen.getByLabelText(/display name/i), 'Updated Name');
        await userEvent.click(screen.getByText(/save/i));
        
        expect(await screen.findByText('Profile updated successfully')).toBeInTheDocument();
        expect(mockApiClient.post).toHaveBeenCalledWith('/profile/user/3', expect.objectContaining({
            displayName: 'Updated Name'
        }));
    });

    it('should handle errors gracefully', async () => {
        // Mock API error
        (mockApiClient.fetch as jest.Mock).mockRejectedValueOnce(new Error('API Error'));

        // Register and initialize the feature
        await feature.register(mockContext);
        await feature.init();

        // Get the rendered element
        const element = feature.render({ userId: 3 });
        render(
            <UserProvider>
                <ProfileProvider api={mockApiClient} userId={3}>
                    {element}
                </ProfileProvider>
            </UserProvider>
        );

        // Wait for error message
        await waitFor(() => {
            expect(await screen.findByText('Failed to load profile')).toBeInTheDocument();
        });
    });
}); 