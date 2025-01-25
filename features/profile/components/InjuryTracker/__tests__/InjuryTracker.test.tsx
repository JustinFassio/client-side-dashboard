import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { InjuryTracker } from '../index';
import { Injury } from '../../../types/profile';

describe('InjuryTracker', () => {
    const mockOnChange = jest.fn();
    const mockOnSave = jest.fn();
    const mockInjuries: Injury[] = [
        {
            id: '1',
            name: 'Knee Pain',
            details: 'Pain during squats',
            severity: 'medium',
            status: 'active',
            type: 'knee_pain',
            description: 'Knee pain during exercise',
            date: '2024-01-01T00:00:00.000Z'
        }
    ];

    beforeEach(() => {
        jest.clearAllMocks();
    });

    it('renders correctly with injuries', () => {
        render(
            <InjuryTracker
                injuries={mockInjuries}
                onChange={mockOnChange}
                onSave={mockOnSave}
                isSaving={false}
            />
        );

        expect(screen.getByText('Injury Tracker')).toBeInTheDocument();
        // Check for the injury title in the list
        expect(screen.getByRole('heading', { name: 'Knee Pain' })).toBeInTheDocument();
        expect(screen.getByDisplayValue('Pain during squats')).toBeInTheDocument();
    });

    it('shows error message when provided', () => {
        const error = 'Test error message';
        render(
            <InjuryTracker
                injuries={mockInjuries}
                onChange={mockOnChange}
                onSave={mockOnSave}
                isSaving={false}
                error={error}
            />
        );

        expect(screen.getByText(error)).toBeInTheDocument();
    });

    it('calls onChange when adding a new injury', () => {
        render(
            <InjuryTracker
                injuries={mockInjuries}
                onChange={mockOnChange}
                onSave={mockOnSave}
                isSaving={false}
            />
        );

        // Select a predefined injury
        const select = screen.getAllByRole('combobox')[0]; // Get the first select element (predefined items)
        fireEvent.change(select, { target: { value: 'knee_pain' } });

        // Find and click the add button
        const addButton = screen.getByRole('button', { name: 'Add' });
        fireEvent.click(addButton);

        expect(mockOnChange).toHaveBeenCalled();
    });

    it('calls onChange when updating an injury', () => {
        render(
            <InjuryTracker
                injuries={mockInjuries}
                onChange={mockOnChange}
                onSave={mockOnSave}
                isSaving={false}
            />
        );

        // Find and change the details field
        const detailsField = screen.getByPlaceholderText('Add details about this injury...');
        fireEvent.change(detailsField, { target: { value: 'Updated details' } });

        expect(mockOnChange).toHaveBeenCalled();
    });

    it('calls onChange when removing an injury', () => {
        render(
            <InjuryTracker
                injuries={mockInjuries}
                onChange={mockOnChange}
                onSave={mockOnSave}
                isSaving={false}
            />
        );

        const removeButton = screen.getByRole('button', { name: 'Remove item' });
        fireEvent.click(removeButton);

        expect(mockOnChange).toHaveBeenCalled();
    });

    it('calls onSave when save button is clicked', () => {
        render(
            <InjuryTracker
                injuries={mockInjuries}
                onChange={mockOnChange}
                onSave={mockOnSave}
                isSaving={false}
            />
        );

        const saveButton = screen.getByRole('button', { name: 'Save Injury Information' });
        fireEvent.click(saveButton);

        expect(mockOnSave).toHaveBeenCalled();
    });

    it('shows loading state when saving', () => {
        render(
            <InjuryTracker
                injuries={mockInjuries}
                onChange={mockOnChange}
                onSave={mockOnSave}
                isSaving={true}
            />
        );

        const saveButton = screen.getByRole('button', { name: 'Loading...' });
        expect(saveButton).toHaveAttribute('aria-busy', 'true');
    });
}); 