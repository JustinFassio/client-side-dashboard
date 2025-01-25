import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { PhysicalSection } from '../PhysicalSection';
import { physicalApi } from '../../../api/physical';

jest.mock('../../../api/physical');

describe('PhysicalSection', () => {
    const mockPhysicalData = {
        height: 180,
        weight: 75,
        units: { height: 'cm', weight: 'kg', measurements: 'cm' },
        preferences: { showMetric: true }
    };

    const mockHistoryData = {
        items: [
            {
                id: '1',
                date: '2024-01-01',
                height: 180,
                weight: 75,
                units_height: 'cm',
                units_weight: 'kg',
                units_measurements: 'cm'
            }
        ],
        total: 1,
        limit: 10,
        offset: 0
    };

    const mockOnSave = () => Promise.resolve();

    beforeEach(() => {
        (physicalApi.getPhysicalData as jest.Mock).mockResolvedValue(mockPhysicalData);
        (physicalApi.updatePhysicalData as jest.Mock).mockImplementation((userId, data) => {
            // Return the data that was passed in, simulating a successful update
            return Promise.resolve(data);
        });
        (physicalApi.getPhysicalHistory as jest.Mock).mockResolvedValue(mockHistoryData);
    });

    it('renders correctly with physical data', async () => {
        render(<PhysicalSection userId={123} onSave={mockOnSave} />);
        await screen.findByText('Physical Information');
        
        // Verify history is loaded
        await waitFor(() => {
            expect(screen.getByText('12/31/2023')).toBeInTheDocument();
        });
    });

    it('shows error message when loading fails', async () => {
        (physicalApi.getPhysicalData as jest.Mock).mockRejectedValue(new Error('Failed to load'));
        render(<PhysicalSection userId={123} onSave={mockOnSave} />);
        await screen.findByRole('alert');
    });

    it('shows loading state when saving', async () => {
        render(<PhysicalSection userId={123} onSave={mockOnSave} isSaving={true} />);
        const saveButton = await screen.findByRole('button', { name: 'Loading...' });
        expect(saveButton).toBeInTheDocument();
        expect(saveButton).toHaveAttribute('aria-busy', 'true');
    });

    it('calls onSave when save button is clicked', async () => {
        const onSave = jest.fn().mockResolvedValue(undefined);
        render(<PhysicalSection userId={123} onSave={onSave} />);
        const saveButton = await screen.findByRole('button', { name: 'Save Changes' });
        fireEvent.click(saveButton);
        await waitFor(() => {
            expect(onSave).toHaveBeenCalled();
        });
    });

    describe('unit conversion', () => {
        it('toggles between metric and imperial units', async () => {
            render(<PhysicalSection userId={123} onSave={mockOnSave} />);
            
            // Wait for initial render
            await screen.findByText('Physical Information');
            
            // Find and click the Imperial button
            const imperialButton = screen.getByRole('radio', { name: 'Imperial' });
            fireEvent.click(imperialButton);
            
            // Wait for the values to update
            await waitFor(() => {
                const heightFeetInput = screen.getByLabelText(/height in feet/i);
                const heightInchesInput = screen.getByLabelText(/height in inches/i);
                const weightInput = screen.getByLabelText(/weight/i);
                
                expect(heightFeetInput).toHaveValue(5); // 180cm ≈ 5'11"
                expect(heightInchesInput).toHaveValue(11);
                expect(weightInput).toHaveValue(165.35); // 75kg ≈ 165.35lbs
            });
        });
    });
}); 