import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { BasicSection } from '../BasicSection';
import { ProfileData } from '../../../../types/profile';
import { FormValidationResult } from '../../../../types/validation';

describe('BasicSection', () => {
    const mockData: Partial<ProfileData> = {
        firstName: 'John',
        lastName: 'Doe',
        displayName: 'JohnDoe',
        email: 'john@example.com'
    };

    const mockOnChange = jest.fn();
    const mockOnSave = jest.fn();

    beforeEach(() => {
        jest.clearAllMocks();
    });

    it('renders correctly with all fields', () => {
        render(
            <BasicSection
                data={mockData}
                onChange={mockOnChange}
                onSave={mockOnSave}
            />
        );

        expect(screen.getByLabelText(/First Name/)).toHaveValue('John');
        expect(screen.getByLabelText(/Last Name/)).toHaveValue('Doe');
        expect(screen.getByLabelText(/Display Name/)).toHaveValue('JohnDoe');
        expect(screen.getByLabelText(/Email/)).toHaveValue('john@example.com');

        const saveButton = screen.getByRole('button', { name: 'Save Basic Information' });
        expect(saveButton).toHaveClass('btn--feature-physical');
    });

    it('shows validation errors when provided', () => {
        const validation: FormValidationResult = {
            isValid: false,
            fieldErrors: {
                firstName: ['First name is required']
            },
            generalErrors: []
        };

        render(
            <BasicSection
                data={mockData}
                onChange={mockOnChange}
                onSave={mockOnSave}
                validation={validation}
            />
        );

        expect(screen.getByText('First name is required')).toBeInTheDocument();
    });

    it('shows error message when provided', () => {
        render(
            <BasicSection
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
            <BasicSection
                data={mockData}
                onChange={mockOnChange}
                onSave={mockOnSave}
            />
        );

        fireEvent.change(screen.getByLabelText(/First Name/), {
            target: { value: 'Jane' }
        });

        expect(mockOnChange).toHaveBeenCalledWith('firstName', 'Jane');
    });

    it('calls onSave when save button is clicked', () => {
        render(
            <BasicSection
                data={mockData}
                onChange={mockOnChange}
                onSave={mockOnSave}
            />
        );

        const saveButton = screen.getByRole('button', { name: 'Save Basic Information' });
        expect(saveButton).toHaveClass('btn--feature-physical');
        fireEvent.click(saveButton);

        expect(mockOnSave).toHaveBeenCalled();
    });

    it('shows loading state when saving', () => {
        render(
            <BasicSection
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