import React, { useState, useEffect } from 'react';
import { PhysicalData } from '../../types';
import * as styles from './PhysicalSection.module.css';

interface MeasurementFormProps {
  initialData: PhysicalData;
  onUpdate: (data: PhysicalData) => Promise<void>;
}

export const MeasurementForm: React.FC<MeasurementFormProps> = ({ initialData, onUpdate }) => {
  const [formState, setFormState] = useState<PhysicalData>(initialData);
  const [submitting, setSubmitting] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);

  useEffect(() => {
    setFormState({
      ...initialData,
      preferences: {
        trackHistory: false,
        showMetricAndImperial: false,
        ...initialData.preferences
      }
    });
  }, [initialData]);

  const handleInputChange = (field: keyof PhysicalData, value: any) => {
    setFormState(prev => ({
      ...prev,
      [field]: typeof value === 'string' ? parseFloat(value) || 0 : value
    }));
  };

  const handleUnitSwitch = (useMetric: boolean) => {
    setFormState(prev => ({
      ...prev,
      units: useMetric ? 'metric' : 'imperial',
      preferences: {
        ...prev.preferences,
        showMetricAndImperial: !useMetric
      }
    }));
  };

  const getUnitLabel = (field: 'height' | 'weight' | 'measurements'): string => {
    if (formState.units === 'metric') {
      return field === 'height' ? 'cm' : field === 'weight' ? 'kg' : 'cm';
    }
    return field === 'height' ? 'ft' : field === 'weight' ? 'lbs' : 'in';
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setFormError(null);
    setSubmitting(true);

    try {
      await onUpdate(formState);
    } catch (error: any) {
      if (error.code === 'invalid_bmi') {
        setFormError(`BMI must be between ${error.data.min_bmi} and ${error.data.max_bmi}`);
      } else if (error.code === 'invalid_measurement') {
        setFormError(error.message);
      } else {
        setFormError('Failed to update measurements');
      }
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className={styles['measurement-form']} aria-label="Physical Measurements">
      {formError && (
        <div className={styles['error-message']} role="alert" aria-live="polite">
          {formError}
        </div>
      )}

      <fieldset>
        <legend>Basic Measurements</legend>
        <div className={styles['form-group']}>
          <label htmlFor="height">Height</label>
          <div className={styles['input-wrapper']}>
            <input
              id="height"
              type="number"
              step="0.1"
              value={formState.height}
              onChange={(e) => handleInputChange('height', e.target.value)}
              required
              aria-required="true"
              min="0"
              max="300"
            />
            <span aria-label="unit">{getUnitLabel('height')}</span>
          </div>
        </div>

        <div className={styles['form-group']}>
          <label htmlFor="weight">Weight</label>
          <div className={styles['input-wrapper']}>
            <input
              id="weight"
              type="number"
              step="0.1"
              value={formState.weight}
              onChange={(e) => handleInputChange('weight', e.target.value)}
              required
              aria-required="true"
              min="0"
            />
            <span aria-label="unit">{getUnitLabel('weight')}</span>
          </div>
        </div>
      </fieldset>

      <fieldset>
        <legend>Additional Measurements</legend>
        <div className={styles['form-group']}>
          <label htmlFor="chest">Chest</label>
          <div className={styles['input-wrapper']}>
            <input
              id="chest"
              type="number"
              step="0.1"
              value={formState.chest || ''}
              onChange={(e) => handleInputChange('chest', e.target.value)}
              min="0"
            />
            <span aria-label="unit">{getUnitLabel('measurements')}</span>
          </div>
        </div>

        <div className={styles['form-group']}>
          <label htmlFor="waist">Waist</label>
          <div className={styles['input-wrapper']}>
            <input
              id="waist"
              type="number"
              step="0.1"
              value={formState.waist || ''}
              onChange={(e) => handleInputChange('waist', e.target.value)}
              min="0"
            />
            <span aria-label="unit">{getUnitLabel('measurements')}</span>
          </div>
        </div>

        <div className={styles['form-group']}>
          <label htmlFor="hips">Hips</label>
          <div className={styles['input-wrapper']}>
            <input
              id="hips"
              type="number"
              step="0.1"
              value={formState.hips || ''}
              onChange={(e) => handleInputChange('hips', e.target.value)}
              min="0"
            />
            <span aria-label="unit">{getUnitLabel('measurements')}</span>
          </div>
        </div>
      </fieldset>

      <fieldset>
        <legend>Preferences</legend>
        <div className={styles['form-group']}>
          <label>
            <input
              type="checkbox"
              checked={formState.preferences?.trackHistory ?? false}
              onChange={(e) => handleInputChange('preferences', {
                ...formState.preferences,
                trackHistory: e.target.checked
              })}
            />
            Track measurement history
          </label>
        </div>

        <div className={styles['form-group']}>
          <label>
            <input
              type="checkbox"
              checked={formState.preferences?.showMetricAndImperial ?? false}
              onChange={(e) => handleInputChange('preferences', {
                ...formState.preferences,
                showMetricAndImperial: e.target.checked
              })}
            />
            Show both metric and imperial units
          </label>
        </div>
      </fieldset>

      <button 
        type="submit" 
        disabled={submitting}
        aria-busy={submitting}
      >
        {submitting ? 'Saving...' : 'Save Changes'}
      </button>
    </form>
  );
}; 