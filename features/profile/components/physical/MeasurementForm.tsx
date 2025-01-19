import React, { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { Section } from '../../components/Section';
import { FormField } from '../form/fields/FormField';
import { PhysicalData } from '../../types/physical';
import { Button } from '../../../../dashboard/components/Button';

interface MeasurementFormProps {
  initialData: PhysicalData;
  onUpdate: (data: PhysicalData) => Promise<void>;
}

export const MeasurementForm: React.FC<MeasurementFormProps> = ({ initialData, onUpdate }) => {
  const [formState, setFormState] = useState<PhysicalData>(initialData);
  const [submitting, setSubmitting] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);

  useEffect(() => {
    console.log('Initializing form with data:', initialData);
    setFormState({
      ...initialData,
      preferences: initialData.preferences || {
        showMetric: true
      }
    });
    console.log('Form state initialized with preferences:', initialData.preferences);
  }, [initialData]);

  const handleInputChange = (field: keyof PhysicalData, value: any) => {
    setFormState(prev => {
      if (field === 'preferences') {
        console.log('Updating preferences:', { field, current: prev.preferences, new: value });
        return {
          ...prev,
          preferences: {
            ...prev.preferences,
            ...value
          }
        };
      }

      // Handle numeric fields
      if (typeof prev[field] === 'number' || field === 'chest' || field === 'waist' || field === 'hips') {
        return {
          ...prev,
          [field]: value === '' ? undefined : parseFloat(value) || 0
        };
      }

      return {
        ...prev,
        [field]: value
      };
    });
  };

  const handleUnitSwitch = (useMetric: boolean) => {
    setFormState(prev => {
      const newUnits = {
        height: useMetric ? ('cm' as const) : ('ft' as const),
        weight: useMetric ? ('kg' as const) : ('lbs' as const),
        measurements: useMetric ? ('cm' as const) : ('in' as const)
      };
      
      // Convert existing values
      let newHeight = prev.height;
      let newHeightFeet: number | undefined;
      let newHeightInches: number | undefined;

      if (useMetric) {
        // Converting from imperial to metric
        if (prev.heightFeet !== undefined && prev.heightInches !== undefined) {
          // Use feet/inches if available
          newHeight = (prev.heightFeet + (prev.heightInches / 12)) * 30.48;
        } else {
          // Fallback to converting the single height value
          newHeight = prev.height;
        }
        newHeightFeet = undefined;
        newHeightInches = undefined;
      } else {
        // Converting from metric to imperial
        const totalFeet = prev.height / 30.48;
        newHeightFeet = Math.floor(totalFeet);
        newHeightInches = Math.round((totalFeet % 1) * 12);
        newHeight = prev.height; // Keep the original cm value for storage
      }

      const convertedValues = {
        height: newHeight,
        heightFeet: newHeightFeet,
        heightInches: newHeightInches,
        weight: prev.weight ? convertMeasurement(prev.weight, prev.units.weight, newUnits.weight) : prev.weight,
        chest: prev.chest ? convertMeasurement(prev.chest, prev.units.measurements, newUnits.measurements) : prev.chest,
        waist: prev.waist ? convertMeasurement(prev.waist, prev.units.measurements, newUnits.measurements) : prev.waist,
        hips: prev.hips ? convertMeasurement(prev.hips, prev.units.measurements, newUnits.measurements) : prev.hips
      };

      console.log('Switching units:', { 
        from: prev.units, 
        to: newUnits,
        oldValues: { height: prev.height, heightFeet: prev.heightFeet, heightInches: prev.heightInches },
        newValues: convertedValues 
      });

      return {
        ...prev,
        ...convertedValues,
        units: newUnits,
        preferences: {
          ...prev.preferences,
          showMetric: useMetric
        }
      };
    });
  };

  const convertMeasurement = (value: number, fromUnit: string, toUnit: string): number => {
    if (fromUnit === toUnit) return value;
    
    // Convert to metric first if coming from imperial
    let metricValue = value;
    if (fromUnit === 'ft') metricValue = value * 30.48;  // ft to cm
    else if (fromUnit === 'in') metricValue = value * 2.54;  // in to cm
    else if (fromUnit === 'lbs') metricValue = value * 0.453592;  // lbs to kg

    // Then convert to imperial if needed
    if (toUnit === 'ft') return Number((metricValue / 30.48).toFixed(2));  // cm to ft
    if (toUnit === 'in') return Number((metricValue / 2.54).toFixed(2));   // cm to in
    if (toUnit === 'lbs') return Number((metricValue / 0.453592).toFixed(2)); // kg to lbs
    
    return Number(metricValue.toFixed(2)); // Return metric value
  };

  const getUnitLabel = (field: 'height' | 'weight' | 'measurements'): string => {
    const { units } = formState;
    return units[field];
  };

  const handleHeightChange = (unit: 'feet' | 'inches', value: string) => {
    const feetVal = unit === 'feet'
      ? parseFloat(value) || 0
      : (formState.heightFeet ?? 0);
    const inchesVal = unit === 'inches'
      ? parseFloat(value) || 0
      : (formState.heightInches ?? 0);

    // Update local form state for separate tracking
    setFormState(prev => ({
      ...prev,
      heightFeet: feetVal,
      heightInches: inchesVal,
      // Convert total feet to cm for storage
      height: (feetVal + (inchesVal / 12)) * 30.48
    }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (submitting) return;

    try {
        setSubmitting(true);
        await onUpdate({
            ...formState,
            preferences: {
                showMetric: formState.preferences.showMetric
            }
        });
        setFormError(null);
    } catch (err) {
        setFormError(err instanceof Error ? err.message : 'Failed to update measurements');
    } finally {
        setSubmitting(false);
    }
  };

  return (
    <Section title={__('Physical Information')}>
      <form onSubmit={handleSubmit} className="form-section__grid" aria-label="Physical Measurements">
        {formError && (
          <div className="form-error" role="alert" aria-live="polite">
            {formError}
          </div>
        )}

        <fieldset>
          <legend>{__('Basic Measurements')}</legend>
          <div className="form-group">
            <label htmlFor="height">{__('Height')}</label>
            {!formState.preferences?.showMetric ? (
              <div className="input-wrapper">
                <input
                  id="height-feet"
                  type="number"
                  value={formState.heightFeet ?? Math.floor(formState.height / 30.48)}
                  onChange={(e) => handleHeightChange('feet', e.target.value)}
                  min="0"
                  max="8"
                  required
                  aria-required="true"
                />
                <span aria-label="feet">ft</span>
                <input
                  id="height-inches"
                  type="number"
                  value={formState.heightInches ?? Math.round((formState.height / 30.48 % 1) * 12)}
                  onChange={(e) => handleHeightChange('inches', e.target.value)}
                  min="0"
                  max="11"
                  required
                  aria-required="true"
                />
                <span aria-label="inches">in</span>
              </div>
            ) : (
              <div className="input-wrapper">
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
            )}
          </div>

          <div className="form-group">
            <label htmlFor="weight">{__('Weight')}</label>
            <div className="input-wrapper">
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
          <legend>{__('Additional Measurements')}</legend>
          <div className="form-group">
            <label htmlFor="chest">{__('Chest')}</label>
            <div className="input-wrapper">
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

          <div className="form-group">
            <label htmlFor="waist">{__('Waist')}</label>
            <div className="input-wrapper">
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

          <div className="form-group">
            <label htmlFor="hips">{__('Hips')}</label>
            <div className="input-wrapper">
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
          <legend>{__('Preferences')}</legend>
          <div className="form-group preferences-group">
            <label>
              <input
                type="checkbox"
                checked={formState.preferences?.showMetric ?? false}
                onChange={(e) => handleInputChange('preferences', {
                  ...formState.preferences,
                  showMetric: e.target.checked
                })}
              />
              {__('Show Metric')}
            </label>
          </div>

          <div className="form-group unit-toggle">
            <span className="toggle-label" id="unit-system-label">{__('Unit System')}</span>
            <div 
              className="toggle-buttons" 
              role="radiogroup" 
              aria-labelledby="unit-system-label"
              aria-label={__('Select unit system')}
            >
              <Button
                type="button"
                variant="secondary"
                feature="physical"
                onClick={() => {
                  handleUnitSwitch(true);
                  handleInputChange('preferences', {
                    ...formState.preferences,
                    showMetric: true
                  });
                }}
                disabled={submitting}
                aria-checked={formState.preferences?.showMetric === true}
                role="radio"
                tabIndex={formState.preferences?.showMetric ? 0 : -1}
                className={formState.preferences?.showMetric ? 'active' : ''}
              >
                {__('Metric')}
              </Button>
              <Button
                type="button"
                variant="secondary"
                feature="physical"
                onClick={() => {
                  handleUnitSwitch(false);
                  handleInputChange('preferences', {
                    ...formState.preferences,
                    showMetric: false
                  });
                }}
                disabled={submitting}
                aria-checked={formState.preferences?.showMetric === false}
                role="radio"
                tabIndex={!formState.preferences?.showMetric ? 0 : -1}
                className={!formState.preferences?.showMetric ? 'active' : ''}
              >
                {__('Imperial')}
              </Button>
            </div>
          </div>
        </fieldset>

        <Button 
          type="submit"
          disabled={submitting}
          aria-busy={submitting}
          feature="physical"
          variant="primary"
        >
          {submitting ? __('Saving...') : __('Save Changes')}
        </Button>
      </form>
    </Section>
  );
}; 