import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { AccountSection } from '../AccountSection';
import { ProfileData } from '../../../../types/profile';
import { FormValidationResult } from '../../../../types/validation';

describe('AccountSection', () => {
    const mockData: Partial<ProfileData> = {
        email: 'john@example.com',
        displayName: 'JohnDoe',
        nickname: 'Johnny'
    };

    const mockOnChange = jest.fn();
    const mockOnSave = jest.fn();

    beforeEach(() => {
        jest.clearAllMocks();
    });

    it('renders correctly with all fields', () => {
        render(
            <AccountSection
                data={mockData}
                onChange={mockOnChange}
                onSave={mockOnSave}
            />
        );

        expect(screen.getByLabelText(/Email/)).toHaveValue('john@example.com');
        expect(screen.getByLabelText(/Display Name/)).toHaveValue('JohnDoe');
        expect(screen.getByLabelText(/Nickname/)).toHaveValue('Johnny');

        const saveButton = screen.getByRole('button', { name: 'Save Account Settings' });
        expect(saveButton).toHaveClass('btn--feature-physical');
    });

    it('shows validation errors when provided', () => {
        const validation: FormValidationResult = {
            isValid: false,
            fieldErrors: {
                email: ['Email is required']
            },
            generalErrors: []
        };

        render(
            <AccountSection
                data={mockData}
                onChange={mockOnChange}
                onSave={mockOnSave}
                validation={validation}
            />
        );

        expect(screen.getByText('Email is required')).toBeInTheDocument();
    });

    it('shows error message when provided', () => {
        render(
            <AccountSection
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
            <AccountSection
                data={mockData}
                onChange={mockOnChange}
                onSave={mockOnSave}
            />
        );

        fireEvent.change(screen.getByLabelText(/Email/), {
            target: { value: 'newemail@example.com' }
        });

        expect(mockOnChange).toHaveBeenCalledWith('email', 'newemail@example.com');
    });

    it('calls onSave when save button is clicked', () => {
        render(
            <AccountSection
                data={mockData}
                onChange={mockOnChange}
                onSave={mockOnSave}
            />
        );

        const saveButton = screen.getByRole('button', { name: 'Save Account Settings' });
        expect(saveButton).toHaveClass('btn--feature-physical');
        fireEvent.click(saveButton);

        expect(mockOnSave).toHaveBeenCalled();
    });

    it('shows loading state when saving', () => {
        render(
            <AccountSection
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