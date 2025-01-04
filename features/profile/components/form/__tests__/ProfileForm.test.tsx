import React from 'react';
import { render, fireEvent, screen } from '@testing-library/react';
import { ProfileForm } from '../ProfileForm';
import { ProfileData } from '../../../types/profile';

describe('ProfileForm', () => {
    const mockProfile: ProfileData = {
        id: 1,
        username: 'testuser',
        displayName: 'Test User',
        email: 'test@example.com',
        firstName: 'Test',
        lastName: 'User',
        age: 25,
        gender: 'male',
        height: 180,
        weight: 75,
        fitnessLevel: 'intermediate',
        activityLevel: 'moderately_active',
        medicalConditions: [],
        exerciseLimitations: [],
        medications: '',
        physicalMetrics: []
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

    it('handles form submission', () => {
        render(
            <ProfileForm
                profile={mockProfile}
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        const displayNameInput = screen.getByDisplayValue('Test User');
        fireEvent.change(displayNameInput, { target: { value: 'Updated Name' } });

        const form = screen.getByRole('form');
        fireEvent.submit(form);

        expect(mockOnSubmit).toHaveBeenCalledWith({
            ...mockProfile,
            displayName: 'Updated Name'
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
}); 