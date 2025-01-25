import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MeasurementForm } from '../../components/physical/MeasurementForm';
import { PhysicalData } from '../../types/physical';

describe('MeasurementForm', () => {
  const mockOnUpdate = jest.fn();
  
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders with initial values', () => {
    const initialData: PhysicalData = {
      height: 175,
      weight: 70,
      units: {
        height: 'cm',
        weight: 'kg',
        measurements: 'cm'
      },
      preferences: {
        showMetric: true
      }
    };

    render(
      <MeasurementForm initialData={initialData} onUpdate={mockOnUpdate} />
    );

    const heightInput = screen.getByRole('spinbutton', { name: /height/i });
    const weightInput = screen.getByRole('spinbutton', { name: /weight/i });
    expect(heightInput).toHaveValue(175);
    expect(weightInput).toHaveValue(70);
  });

  it('validates height input', async () => {
    const initialData: PhysicalData = {
      height: 175,
      weight: 70,
      units: {
        height: 'cm',
        weight: 'kg',
        measurements: 'cm'
      },
      preferences: {
        showMetric: true
      }
    };

    render(
      <MeasurementForm initialData={initialData} onUpdate={mockOnUpdate} />
    );

    const heightInput = screen.getByRole('spinbutton', { name: /height/i });
    await userEvent.clear(heightInput);
    await userEvent.type(heightInput, '99');
    fireEvent.blur(heightInput);

    await waitFor(() => {
      expect(screen.getByRole('alert')).toHaveTextContent(/height must be between/i);
    });
  });

  it('validates weight input', async () => {
    const initialData: PhysicalData = {
      height: 175,
      weight: 70,
      units: {
        height: 'cm',
        weight: 'kg',
        measurements: 'cm'
      },
      preferences: {
        showMetric: true
      }
    };

    render(
      <MeasurementForm initialData={initialData} onUpdate={mockOnUpdate} />
    );

    const weightInput = screen.getByRole('spinbutton', { name: /weight/i });
    await userEvent.clear(weightInput);
    await userEvent.type(weightInput, '29');
    fireEvent.blur(weightInput);

    await waitFor(() => {
      expect(screen.getByRole('alert')).toHaveTextContent(/weight must be between/i);
    });
  });

  it('handles unit conversion', async () => {
    const initialData: PhysicalData = {
      height: 175,
      weight: 70,
      units: {
        height: 'cm',
        weight: 'kg',
        measurements: 'cm'
      },
      preferences: {
        showMetric: true
      }
    };

    render(
      <MeasurementForm initialData={initialData} onUpdate={mockOnUpdate} />
    );

    const imperialRadio = screen.getByRole('radio', { name: /imperial/i });
    await userEvent.click(imperialRadio);

    await waitFor(() => {
      expect(mockOnUpdate).toHaveBeenCalledWith(expect.objectContaining({
        preferences: { showMetric: false }
      }));
    });
  });

  it('converts values correctly when switching between metric and imperial', async () => {
    const initialData: PhysicalData = {
      height: 180.34,
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

    const { getByLabelText, getByRole } = render(
      <MeasurementForm initialData={initialData} onUpdate={mockOnUpdate} />
    );
    
    // Get input fields
    const heightInput = getByLabelText('Height in centimeters');
    const weightInput = getByLabelText('Weight in kg');
    
    // Switch to imperial
    const imperialButton = getByRole('radio', { name: /imperial/i });
    await userEvent.click(imperialButton);
    
    // Check imperial values (5'11" and 176.37 lbs)
    const heightFeetInput = getByLabelText('Height in feet');
    const heightInchesInput = getByLabelText('Height in inches');
    const weightLbsInput = getByLabelText('Weight in lbs');

    expect(heightFeetInput).toHaveValue(5);
    expect(heightInchesInput).toHaveValue(11);
    expect(weightLbsInput).toHaveValue(176.37);

    // Switch back to metric
    const metricButton = getByRole('radio', { name: /metric/i });
    await userEvent.click(metricButton);

    // Check metric values are restored
    const heightInputMetric = getByLabelText('Height in centimeters');
    const weightInputMetric = getByLabelText('Weight in kg');

    expect(heightInputMetric).toHaveValue(180.34);
    expect(weightInputMetric).toHaveValue(80);
  });

  it('handles form submission with converted values', async () => {
    const initialData: PhysicalData = {
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

    const { getByRole } = render(
      <MeasurementForm initialData={initialData} onUpdate={mockOnUpdate} />
    );

    // Switch to imperial
    const imperialButton = getByRole('radio', { name: /imperial/i });
    await userEvent.click(imperialButton);

    // Submit form
    await userEvent.click(getByRole('button', { name: /save changes/i }));

    await waitFor(() => {
      expect(mockOnUpdate).toHaveBeenCalledWith({
        height: 180,
        heightFeet: 5,
        heightInches: 11,
        weight: 176.37,
        units: {
          height: 'ft',
          weight: 'lbs',
          measurements: 'in'
        },
        preferences: {
          showMetric: false
        }
      });
    });
  });
}); 