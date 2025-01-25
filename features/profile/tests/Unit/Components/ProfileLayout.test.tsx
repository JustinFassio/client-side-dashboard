import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { ProfileLayout } from '../../../components/layout/ProfileLayout';
import { useProfile } from '../../../context/ProfileContext';
import { useUser } from '../../../../user/context/UserContext';
import { FeatureContext } from '../../../../../dashboard/contracts/Feature';

// Mock dependencies
jest.mock('../../../context/ProfileContext');
jest.mock('../../../../user/context/UserContext');

describe('ProfileLayout', () => {
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

    beforeEach(() => {
        (useProfile as jest.Mock).mockReturnValue({
            loading: false,
            error: null,
            profile: mockProfile,
            updateUserProfile: jest.fn()
        });

        (useUser as jest.Mock).mockReturnValue({
            user: { ID: 1, display_name: 'Test User' },
            isLoading: false,
            error: null
        });
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    it('renders loading state', () => {
        (useProfile as jest.Mock).mockReturnValue({
            loading: true,
            error: null,
            profile: null,
            updateUserProfile: jest.fn()
        });

        render(<ProfileLayout context={mockContext} />);
        expect(screen.getByRole('status')).toBeInTheDocument();
    });

    it('renders error state', () => {
        const errorMessage = 'Failed to load profile';
        (useProfile as jest.Mock).mockReturnValue({
            loading: false,
            error: new Error(errorMessage),
            profile: null,
            updateUserProfile: jest.fn()
        });

        render(<ProfileLayout context={mockContext} />);
        expect(screen.getByText(errorMessage)).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /retry/i })).toBeInTheDocument();
    });

    it('renders empty state when no profile data', () => {
        (useProfile as jest.Mock).mockReturnValue({
            loading: false,
            error: null,
            profile: null,
            updateUserProfile: jest.fn()
        });

        render(<ProfileLayout context={mockContext} />);
        expect(screen.getByText(/no profile data/i)).toBeInTheDocument();
    });

    it('renders all sections when profile data is available', () => {
        render(<ProfileLayout context={mockContext} />);
        
        expect(screen.getByText(/basic information/i)).toBeInTheDocument();
        expect(screen.getByText(/medical information/i)).toBeInTheDocument();
        expect(screen.getByText(/account information/i)).toBeInTheDocument();
        expect(screen.getByText(/physical information/i)).toBeInTheDocument();
        expect(screen.getByText(/injury tracker/i)).toBeInTheDocument();
    });

    it('handles section data changes', async () => {
        const mockUpdateUserProfile = jest.fn();
        (useProfile as jest.Mock).mockReturnValue({
            loading: false,
            error: null,
            profile: mockProfile,
            updateUserProfile: mockUpdateUserProfile
        });

        render(<ProfileLayout context={mockContext} />);

        // Find and update a form field
        const firstNameInput = screen.getByLabelText(/first name/i);
        fireEvent.change(firstNameInput, { target: { value: 'Updated Name' } });

        // Find and click the save button
        const saveButton = screen.getByRole('button', { name: /save basic information/i });
        fireEvent.click(saveButton);

        await waitFor(() => {
            expect(mockUpdateUserProfile).toHaveBeenCalledWith(1, {
                basic: expect.objectContaining({
                    firstName: 'Updated Name'
                })
            });
        });
    });

    it('displays error message when save fails', async () => {
        const mockUpdateUserProfile = jest.fn().mockRejectedValue(new Error('Save failed'));
        (useProfile as jest.Mock).mockReturnValue({
            loading: false,
            error: null,
            profile: mockProfile,
            updateUserProfile: mockUpdateUserProfile
        });

        render(<ProfileLayout context={mockContext} />);

        // Find and click the save button
        const saveButton = screen.getByRole('button', { name: /save basic information/i });
        fireEvent.click(saveButton);

        await waitFor(() => {
            expect(screen.getByText('Save failed')).toBeInTheDocument();
        });
    });

    it('renders children prop', () => {
        render(
            <ProfileLayout context={mockContext}>
                <div data-testid="test-child">Child Content</div>
            </ProfileLayout>
        );

        expect(screen.getByTestId('test-child')).toBeInTheDocument();
        expect(screen.getByText('Child Content')).toBeInTheDocument();
    });
}); 