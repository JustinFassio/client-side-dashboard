import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { MedicalSection } from '../MedicalSection';
import { ProfileData } from '../../../../types/profile';
import { FormValidationResult } from '../../../../types/validation';

describe('MedicalSection', () => {
    const mockData: Partial<ProfileData> = {
        medicalConditions: ['asthma'],
        exerciseLimitations: ['joint_pain'],
        medications: 'Inhaler'
    };

    const mockOnChange = jest.fn();
    const mockOnSave = jest.fn();

    beforeEach(() => {
        jest.clearAllMocks();
    });

    it('renders correctly with all fields', () => {
        render(
            <MedicalSection
                data={mockData}
                onChange={mockOnChange}
                onSave={mockOnSave}
            />
        );

        expect(screen.getByLabelText(/Medical Conditions/)).toBeInTheDocument();
        expect(screen.getByLabelText(/Exercise Limitations/)).toBeInTheDocument();
        expect(screen.getByLabelText(/Current Medications/)).toHaveValue('Inhaler');

        const saveButton = screen.getByRole('button', { name: 'Save Medical Information' });
        expect(saveButton).toHaveClass('btn--feature-physical');
    });

    it('shows validation errors when provided', () => {
        const validation: FormValidationResult = {
            isValid: false,
            fieldErrors: {
                medicalConditions: ['Medical conditions are required']
            },
            generalErrors: []
        };

        render(
            <MedicalSection
                data={mockData}
                onChange={mockOnChange}
                onSave={mockOnSave}
                validation={validation}
            />
        );

        expect(screen.getByText('Medical conditions are required')).toBeInTheDocument();
    });

    it('shows error message when provided', () => {
        render(
            <MedicalSection
                data={mockData}
                onChange={mockOnChange}
                onSave={mockOnSave}
                error="Failed to save"
            />
        );

        expect(screen.getByRole('alert')).toHaveTextContent('Failed to save');
    });

    it('calls onChange when fields are updated', () => {
        render(
            <MedicalSection
                data={mockData}
                onChange={mockOnChange}
                onSave={mockOnSave}
            />
        );

        fireEvent.change(screen.getByLabelText(/Current Medications/), {
            target: { value: 'New Medication' }
        });

        expect(mockOnChange).toHaveBeenCalledWith('medications', 'New Medication');
    });

    it('calls onSave when save button is clicked', () => {
        render(
            <MedicalSection
                data={mockData}
                onChange={mockOnChange}
                onSave={mockOnSave}
            />
        );

        const saveButton = screen.getByRole('button', { name: 'Save Medical Information' });
        expect(saveButton).toHaveClass('btn--feature-physical');
        fireEvent.click(saveButton);

        expect(mockOnSave).toHaveBeenCalled();
    });

    it('shows loading state when saving', () => {
        render(
            <MedicalSection
                data={mockData}
                onChange={mockOnChange}
                onSave={mockOnSave}
                isSaving={true}
            />
        );

        const saveButton = screen.getByRole('button', { name: 'Loading...' });
        expect(saveButton).toBeDisabled();
        expect(saveButton).toHaveAttribute('aria-busy', 'true');
        expect(saveButton).toHaveClass('btn--loading', 'btn--feature-physical');
    });
}); 