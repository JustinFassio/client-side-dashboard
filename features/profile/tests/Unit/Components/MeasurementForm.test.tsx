import React from 'react';
import { render, fireEvent, screen } from '@testing-library/react';
import { MeasurementForm } from '../../../components/physical/MeasurementForm';

describe('MeasurementForm', () => {
  const mockOnUpdate = jest.fn();
  
  beforeEach(() => {
    mockOnUpdate.mockClear();
  });

  it('converts values correctly when switching between metric and imperial', () => {
    const initialData = {
      height: 180, // cm
      weight: 80,  // kg
      units: {
        height: 'cm',
        weight: 'kg',
        measurements: 'cm'
      },
      preferences: {
        showMetric: true
      }
    };

    render(<MeasurementForm initialData={initialData} onUpdate={mockOnUpdate} />);

    // Find and click the imperial unit switch
    const imperialSwitch = screen.getByRole('button', { name: /imperial/i });
    fireEvent.click(imperialSwitch);

    // Check if values were converted correctly
    const heightInput = screen.getByLabelText(/height/i);
    const weightInput = screen.getByLabelText(/weight/i);

    expect(heightInput.value).toBe('5.91'); // 180cm ≈ 5.91ft
    expect(weightInput.value).toBe('176.37'); // 80kg ≈ 176.37lbs

    // Switch back to metric
    const metricSwitch = screen.getByRole('button', { name: /metric/i });
    fireEvent.click(metricSwitch);

    // Check if values were converted back correctly
    expect(heightInput.value).toBe('180.00');
    expect(weightInput.value).toBe('80.00');
  });

  it('preserves null values during unit conversion', () => {
    const initialData = {
      height: null,
      weight: 80,
      units: {
        height: 'cm',
        weight: 'kg',
        measurements: 'cm'
      },
      preferences: {
        showMetric: true
      }
    };

    render(<MeasurementForm initialData={initialData} onUpdate={mockOnUpdate} />);

    // Switch to imperial
    const imperialSwitch = screen.getByRole('button', { name: /imperial/i });
    fireEvent.click(imperialSwitch);

    // Check if null value was preserved
    const heightInput = screen.getByLabelText(/height/i);
    const weightInput = screen.getByLabelText(/weight/i);

    expect(heightInput.value).toBe(''); // Should remain empty
    expect(weightInput.value).toBe('176.37'); // 80kg ≈ 176.37lbs
  });

  it('handles form submission with converted values', async () => {
    const initialData = {
      height: 180,
      weight: 80,
      units: {
        height: 'cm',
        weight: 'kg',
        measurements: 'cm'
      },
      preferences: {
        showMetric: true
      }
    };

    render(<MeasurementForm initialData={initialData} onUpdate={mockOnUpdate} />);

    // Switch to imperial
    const imperialSwitch = screen.getByRole('button', { name: /imperial/i });
    fireEvent.click(imperialSwitch);

    // Submit form
    const submitButton = screen.getByRole('button', { name: /save/i });
    fireEvent.click(submitButton);

    // Check if onUpdate was called with converted values
    expect(mockOnUpdate).toHaveBeenCalledWith(expect.objectContaining({
      height: 5.91,
      weight: 176.37,
      units: {
        height: 'ft',
        weight: 'lbs',
        measurements: 'in'
      }
    }));
  });
}); 