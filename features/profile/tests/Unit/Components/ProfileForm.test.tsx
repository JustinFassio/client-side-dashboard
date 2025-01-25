import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ProfileForm } from '../../../components/form/ProfileForm';
import { ProfileData } from '../../../types/profile';

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

    it('renders form with initial values', () => {
        render(
            <ProfileForm
                profile={mockProfile}
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        expect(screen.getByLabelText(/height/i)).toHaveValue('175cm');
        expect(screen.getByLabelText(/weight/i)).toHaveValue('70kg');
    });

    it('handles form submission', async () => {
        render(
            <ProfileForm
                profile={mockProfile}
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        fireEvent.submit(screen.getByRole('form'));

        await waitFor(() => {
            expect(mockOnSubmit).toHaveBeenCalledWith(expect.objectContaining({
                heightCm: 175,
                weightKg: 70,
                experienceLevel: 'intermediate',
                equipment: ['dumbbells', 'barbell'],
                fitnessGoals: ['strength', 'muscle_gain']
            }));
        });
    });

    it('handles cancel button click', async () => {
        render(
            <ProfileForm
                profile={mockProfile}
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        fireEvent.click(screen.getByRole('button', { name: /cancel/i }));
        expect(mockOnCancel).toHaveBeenCalled();
    });

    it('disables form submission while submitting', () => {
        render(
            <ProfileForm
                profile={mockProfile}
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
                isSubmitting={true}
            />
        );

        const submitButton = screen.getByRole('button', { name: /saving/i });
        expect(submitButton).toBeDisabled();
        expect(submitButton).toHaveAttribute('aria-busy', 'true');
    });

    it('displays validation messages for required fields', async () => {
        render(
            <ProfileForm
                profile={{ ...mockProfile, heightCm: 0, weightKg: 0 }}
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        const submitButton = screen.getByRole('button', { name: /save/i });
        await userEvent.click(submitButton);

        await waitFor(() => {
            expect(screen.getByTestId('height-error')).toBeInTheDocument();
            expect(screen.getByTestId('weight-error')).toBeInTheDocument();
            expect(screen.getByTestId('height-error')).toHaveTextContent(/height must be between/i);
            expect(screen.getByTestId('weight-error')).toHaveTextContent(/weight must be between/i);
        });
    });

    it('handles equipment selection correctly', async () => {
        render(
            <ProfileForm
                profile={mockProfile}
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        await userEvent.selectOptions(
            screen.getByLabelText(/available equipment/i),
            ['dumbbells', 'barbell', 'kettlebell']
        );

        fireEvent.submit(screen.getByRole('form'));

        await waitFor(() => {
            expect(mockOnSubmit).toHaveBeenCalledWith(expect.objectContaining({
                equipment: ['dumbbells', 'barbell', 'kettlebell']
            }));
        });
    });

    it('handles fitness goals selection correctly', async () => {
        const user = userEvent.setup();
        
        // Start with a valid profile including required fields
        const testProfile: ProfileData = {
            ...mockProfile,
            fitnessGoals: [], // Start with no goals selected
            heightCm: 175,
            weightKg: 70,
            experienceLevel: 'intermediate'
        };
        
        render(
            <ProfileForm
                profile={testProfile}
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        // Get the select element and select multiple options
        const goalsSelect = screen.getByLabelText(/fitness goals/i) as HTMLSelectElement;
        const goals = ['strength', 'muscle_gain', 'fat_loss'];
        
        // Wait for the select to be ready
        await waitFor(() => {
            expect(goalsSelect).toBeInTheDocument();
        });
        
        // Select the options
        await user.selectOptions(goalsSelect, goals);
        
        // Verify options are selected
        const selectedOptions = Array.from(goalsSelect.selectedOptions).map(opt => opt.value);
        expect(selectedOptions).toEqual(expect.arrayContaining(goals));

        // Submit the form
        const submitButton = screen.getByRole('button', { name: /save/i });
        await user.click(submitButton);
        
        // Wait for the form submission
        await waitFor(() => {
            expect(mockOnSubmit).toHaveBeenCalledTimes(1);
            const submittedData = mockOnSubmit.mock.calls[0][0];
            expect(submittedData.fitnessGoals).toEqual(expect.arrayContaining(goals));
        });
    });

    it('validates fitness goals selection', async () => {
        const user = userEvent.setup();
        
        render(
            <ProfileForm
                profile={mockProfile}
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        // Get the form and select elements
        const goalsSelect = screen.getByLabelText(/fitness goals/i);

        // Clear all selections if any exist
        if (mockProfile.fitnessGoals?.length) {
            await user.deselectOptions(goalsSelect, mockProfile.fitnessGoals);
        }

        // Submit the form
        const submitButton = screen.getByRole('button', { name: /save/i });
        await user.click(submitButton);
        
        // Check for validation error
        await waitFor(() => {
            const error = screen.getByRole('alert');
            expect(error).toHaveTextContent(/please select at least one fitness goal/i);
        });

        expect(mockOnSubmit).not.toHaveBeenCalled();
    });
}); 