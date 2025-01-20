import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ProfileFeature from '../components/ProfileFeature';

describe('ProfileFeature', () => {
    it('renders profile sections', () => {
        render(<ProfileFeature />);
        
        expect(screen.getByText('Physical Information')).toBeInTheDocument();
        expect(screen.getByText('Experience Level')).toBeInTheDocument();
    });

    it('loads profile data on mount', async () => {
        render(<ProfileFeature />);

        await waitFor(() => {
            expect(screen.getByDisplayValue('180')).toBeInTheDocument();
            expect(screen.getByDisplayValue('75')).toBeInTheDocument();
            expect(screen.getByDisplayValue('intermediate')).toBeInTheDocument();
        });
    });

    it('validates height input', async () => {
        render(<ProfileFeature />);
        const heightInput = screen.getByLabelText('Height (cm)');

        await userEvent.clear(heightInput);
        await userEvent.type(heightInput, '400');

        const saveButton = screen.getByRole('button', { name: /save/i });
        fireEvent.click(saveButton);

        await waitFor(() => {
            expect(screen.getByText(/height must be between/i)).toBeInTheDocument();
        });
    });

    it('validates weight input', async () => {
        render(<ProfileFeature />);
        const weightInput = screen.getByLabelText('Weight (kg)');

        await userEvent.clear(weightInput);
        await userEvent.type(weightInput, '300');

        const saveButton = screen.getByRole('button', { name: /save/i });
        fireEvent.click(saveButton);

        await waitFor(() => {
            expect(screen.getByText(/weight must be between/i)).toBeInTheDocument();
        });
    });

    it('converts units correctly', async () => {
        render(<ProfileFeature />);
        
        // Switch to imperial
        const unitSelect = screen.getByLabelText('Unit System');
        await userEvent.selectOptions(unitSelect, 'imperial');

        await waitFor(() => {
            // 180cm ≈ 5'11"
            expect(screen.getByDisplayValue('5')).toBeInTheDocument();
            expect(screen.getByDisplayValue('11')).toBeInTheDocument();
            // 75kg ≈ 165lbs
            expect(screen.getByDisplayValue('165')).toBeInTheDocument();
        });
    });

    it('saves profile data successfully', async () => {
        render(<ProfileFeature />);

        const heightInput = screen.getByLabelText('Height (cm)');
        const weightInput = screen.getByLabelText('Weight (kg)');

        await userEvent.clear(heightInput);
        await userEvent.type(heightInput, '175');
        await userEvent.clear(weightInput);
        await userEvent.type(weightInput, '70');

        const saveButton = screen.getByRole('button', { name: /save/i });
        fireEvent.click(saveButton);

        await waitFor(() => {
            expect(screen.getByText(/profile updated successfully/i)).toBeInTheDocument();
        });

        expect(global.fetch).toHaveBeenCalledWith(
            expect.stringContaining('/profile/physical'),
            expect.objectContaining({
                method: 'POST',
                body: expect.stringContaining('"heightCm":175'),
            })
        );
    });

    it('handles API errors gracefully', async () => {
        // Mock API error
        (global.fetch as jest.Mock).mockImplementationOnce(() => 
            Promise.reject(new Error('API Error'))
        );

        render(<ProfileFeature />);
        const saveButton = screen.getByRole('button', { name: /save/i });
        fireEvent.click(saveButton);

        await waitFor(() => {
            expect(screen.getByText(/failed to update profile/i)).toBeInTheDocument();
        });
    });

    it('maintains form state during navigation', async () => {
        render(<ProfileFeature />);

        // Fill out form
        const heightInput = screen.getByLabelText('Height (cm)');
        await userEvent.clear(heightInput);
        await userEvent.type(heightInput, '175');

        // Switch sections
        const experienceTab = screen.getByText('Experience Level');
        fireEvent.click(experienceTab);
        const physicalTab = screen.getByText('Physical Information');
        fireEvent.click(physicalTab);

        // Verify form state persists
        expect(screen.getByDisplayValue('175')).toBeInTheDocument();
    });
}); 