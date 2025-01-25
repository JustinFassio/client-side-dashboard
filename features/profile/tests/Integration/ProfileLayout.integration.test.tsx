import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { ProfileLayout } from '../../components/layout/ProfileLayout';
import { ProfileProvider } from '../../context/ProfileContext';
import { UserProvider } from '../../../user/context/UserContext';
import { ApiClient } from '../../../../dashboard/services/api';
import { FeatureContext } from '../../../../dashboard/contracts/Feature';

// Mock ApiClient
jest.mock('../../../../dashboard/services/api');

describe('ProfileLayout Integration', () => {
    const mockContext: FeatureContext = {
        apiUrl: 'http://test.local/wp-json',
        nonce: 'test-nonce',
        debug: false,
        dispatch: jest.fn()
    };

    const mockProfile = {
        user_id: 1,
        data: {
            basic: {
                firstName: 'Test',
                lastName: 'User',
                email: 'test@example.com'
            },
            medical: {
                medicalConditions: [],
                exerciseLimitations: []
            },
            account: {
                username: 'testuser',
                displayName: 'Test User'
            },
            injuries: []
        }
    };

    const mockApiClient = {
        fetch: jest.fn(),
        post: jest.fn()
    };

    beforeEach(() => {
        (ApiClient.getInstance as jest.Mock).mockReturnValue(mockApiClient);
        mockApiClient.fetch.mockResolvedValue({ data: mockProfile });
        mockApiClient.post.mockResolvedValue({ data: mockProfile });
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    const renderWithProviders = (children: React.ReactNode) => {
        return render(
            <UserProvider>
                <ProfileProvider api={mockApiClient as unknown as ApiClient} userId={1}>
                    {children}
                </ProfileProvider>
            </UserProvider>
        );
    };

    it('loads and displays profile data', async () => {
        renderWithProviders(<ProfileLayout context={mockContext} />);

        // Initial loading state
        expect(screen.getByRole('status')).toBeInTheDocument();

        // Wait for profile data to load
        await waitFor(() => {
            expect(screen.getByText(/basic information/i)).toBeInTheDocument();
        });

        // Verify all sections are rendered with data
        expect(screen.getByDisplayValue('Test')).toBeInTheDocument(); // First name
        expect(screen.getByDisplayValue('User')).toBeInTheDocument(); // Last name
        expect(screen.getByDisplayValue('test@example.com')).toBeInTheDocument(); // Email
    });

    it('updates profile data across sections', async () => {
        const updatedProfile = {
            ...mockProfile,
            data: {
                ...mockProfile.data,
                basic: {
                    ...mockProfile.data.basic,
                    firstName: 'Updated'
                }
            }
        };
        mockApiClient.post.mockResolvedValueOnce({ data: updatedProfile });

        renderWithProviders(<ProfileLayout context={mockContext} />);

        // Wait for initial load
        await waitFor(() => {
            expect(screen.getByText(/basic information/i)).toBeInTheDocument();
        });

        // Update first name
        const firstNameInput = screen.getByLabelText(/first name/i);
        fireEvent.change(firstNameInput, { target: { value: 'Updated' } });

        // Save changes
        const saveButton = screen.getByRole('button', { name: /save basic information/i });
        fireEvent.click(saveButton);

        // Verify API call
        await waitFor(() => {
            expect(mockApiClient.post).toHaveBeenCalledWith(
                'profile/user/1',
                expect.objectContaining({
                    basic: expect.objectContaining({
                        firstName: 'Updated'
                    })
                })
            );
        });

        // Verify UI update
        expect(screen.getByDisplayValue('Updated')).toBeInTheDocument();
    });

    it('handles API errors gracefully', async () => {
        mockApiClient.fetch.mockRejectedValueOnce(new Error('Failed to load profile'));

        renderWithProviders(<ProfileLayout context={mockContext} />);

        await waitFor(() => {
            expect(screen.getByText(/failed to load profile/i)).toBeInTheDocument();
        });

        // Verify retry button
        const retryButton = screen.getByRole('button', { name: /retry/i });
        expect(retryButton).toBeInTheDocument();

        // Mock successful retry
        mockApiClient.fetch.mockResolvedValueOnce({ data: mockProfile });
        fireEvent.click(retryButton);

        await waitFor(() => {
            expect(screen.getByText(/basic information/i)).toBeInTheDocument();
        });
    });

    it('maintains state across section updates', async () => {
        renderWithProviders(<ProfileLayout context={mockContext} />);

        // Wait for initial load
        await waitFor(() => {
            expect(screen.getByText(/basic information/i)).toBeInTheDocument();
        });

        // Update multiple sections
        const firstNameInput = screen.getByLabelText(/first name/i);
        const medicalNotesInput = screen.getByLabelText(/medical notes/i);

        fireEvent.change(firstNameInput, { target: { value: 'Updated Name' } });
        fireEvent.change(medicalNotesInput, { target: { value: 'New medical notes' } });

        // Save basic section
        const saveBasicButton = screen.getByRole('button', { name: /save basic information/i });
        fireEvent.click(saveBasicButton);

        // Verify medical notes still maintain their value
        expect(screen.getByDisplayValue('New medical notes')).toBeInTheDocument();

        // Save medical section
        const saveMedicalButton = screen.getByRole('button', { name: /save medical information/i });
        fireEvent.click(saveMedicalButton);

        // Verify both API calls were made
        await waitFor(() => {
            expect(mockApiClient.post).toHaveBeenCalledTimes(2);
        });
    });
}); 