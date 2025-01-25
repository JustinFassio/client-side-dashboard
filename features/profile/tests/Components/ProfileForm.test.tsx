import React from 'react';
import { render, fireEvent, screen, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ProfileForm } from '../../components/form/ProfileForm';
import { ProfileData } from '../../types/profile';

describe('ProfileForm', () => {
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

    const mockOnSubmit = jest.fn();
    const mockOnCancel = jest.fn();

    beforeEach(() => {
        jest.clearAllMocks();
    });

    it('renders profile form with initial data', () => {
        render(
            <ProfileForm
                profile={mockProfile}
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        expect(screen.getByDisplayValue('testuser')).toBeInTheDocument();
        expect(screen.getByDisplayValue('Test User')).toBeInTheDocument();
    });

    it('handles form submission', async () => {
        const user = userEvent.setup();
        render(<ProfileForm profile={mockProfile} onSubmit={mockOnSubmit} onCancel={mockOnCancel} />);

        const displayNameInput = screen.getByLabelText('Display Name');
        await user.clear(displayNameInput);
        await user.type(displayNameInput, 'Updated Name');

        const submitButton = screen.getByRole('button', { name: /save/i });
        await user.click(submitButton);

        await waitFor(() => {
            expect(mockOnSubmit).toHaveBeenCalledWith({
                ...mockProfile,
                displayName: 'Updated Name'
            });
        });
    });

    it('handles form cancellation', () => {
        render(
            <ProfileForm
                profile={mockProfile}
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        const cancelButton = screen.getByText('Cancel');
        fireEvent.click(cancelButton);

        expect(mockOnCancel).toHaveBeenCalled();
    });

    it('handles equipment selection', async () => {
        const user = userEvent.setup();
        render(<ProfileForm profile={mockProfile} onSubmit={mockOnSubmit} onCancel={mockOnCancel} />);

        const equipmentSelect = screen.getByLabelText('Available Equipment');
        await user.selectOptions(equipmentSelect, ['dumbbells', 'resistance_bands']);
        await user.click(screen.getByRole('button', { name: /save/i }));

        await waitFor(() => {
            expect(mockOnSubmit).toHaveBeenCalledWith(expect.objectContaining({
                equipment: ['dumbbells', 'resistance_bands']
            }));
        });
    });

    it('handles fitness goals selection', async () => {
        const user = userEvent.setup();
        render(<ProfileForm profile={mockProfile} onSubmit={mockOnSubmit} onCancel={mockOnCancel} />);

        const goalsSelect = screen.getByLabelText('Fitness Goals');
        await user.selectOptions(goalsSelect, ['strength', 'endurance']);
        await user.click(screen.getByRole('button', { name: /save/i }));

        await waitFor(() => {
            expect(mockOnSubmit).toHaveBeenCalledWith(expect.objectContaining({
                fitnessGoals: ['strength', 'endurance']
            }));
        });
    });
}); 