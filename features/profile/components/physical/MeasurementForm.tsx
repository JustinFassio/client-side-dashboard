import React, { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { Section } from '../../components/Section';
import { FormField } from '../form/fields/FormField';
import { PhysicalData } from '../../types/physical';
import { Button } from '../../../../dashboard/components/Button';

interface MeasurementFormProps {
  initialData: PhysicalData;
  onUpdate: (data: PhysicalData) => Promise<void>;
  isSaving?: boolean;
}

export const MeasurementForm: React.FC<MeasurementFormProps> = ({ initialData, onUpdate, isSaving }) => {
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

  const handleHeightChange = (unit: 'feet' | 'inches', value: string) => {
    const feetVal = unit === 'feet'
      ? parseFloat(value) || 0
      : (formState.heightFeet ?? 0);
    const inchesVal = unit === 'inches'
      ? parseFloat(value) || 0
      : (formState.heightInches ?? 0);

    // Validate inches to be between 0 and 11
    const validatedInches = Math.min(Math.max(inchesVal, 0), 11);

    // Store feet/inches values
    setFormState(prev => {
      // Always store height in centimeters internally
      const heightInFeet = feetVal + (validatedInches / 12);
      const heightInCm = heightInFeet * 30.48;

      return {
        ...prev,
        heightFeet: feetVal,
        heightInches: validatedInches,
        // Always store in centimeters
        height: heightInCm,
        units: {
          ...prev.units,
          height: prev.preferences?.showMetric ? 'cm' : 'ft'
        }
      };
    });
  };

  const handleUnitSwitch = async (useMetric: boolean) => {
    setFormState(prev => {
      const newUnits = {
        height: useMetric ? ('cm' as const) : ('ft' as const),
        weight: useMetric ? ('kg' as const) : ('lbs' as const),
        measurements: useMetric ? ('cm' as const) : ('in' as const)
      };
      
      // Height is always stored in centimeters internally
      let heightInCm = prev.height;
      let newHeightFeet: number | undefined;
      let newHeightInches: number | undefined;

      if (!useMetric) {
        // Calculate feet and inches for display
        const totalFeet = heightInCm / 30.48;
        newHeightFeet = Math.floor(totalFeet);
        newHeightInches = Math.round((totalFeet % 1) * 12);
      }

      const convertedValues = {
        // Height stays in cm internally
        heightFeet: newHeightFeet,
        heightInches: newHeightInches,
        weight: prev.weight ? convertMeasurement(prev.weight, prev.units.weight, newUnits.weight) : prev.weight,
        chest: prev.chest ? convertMeasurement(prev.chest, prev.units.measurements, newUnits.measurements) : prev.chest,
        waist: prev.waist ? convertMeasurement(prev.waist, prev.units.measurements, newUnits.measurements) : prev.waist,
        hips: prev.hips ? convertMeasurement(prev.hips, prev.units.measurements, newUnits.measurements) : prev.hips
      };

      const newState = {
        ...prev,
        ...convertedValues,
        units: newUnits,
        preferences: {
          ...prev.preferences,
          showMetric: useMetric
        }
      };

      // Call onUpdate with the new state
      const result = onUpdate(newState);
      if (result && typeof result.catch === 'function') {
        result.catch(console.error);
      }

      return newState;
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

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    console.log('Submitting physical data:', formState);
    await onUpdate(formState);
  };

  return (
    <Section title={__('Physical Information')}>
      <form onSubmit={handleSubmit} className="form-section__grid" aria-label="Physical Measurements">
        {formError && (
          <div className="form-error" role="alert" aria-live="polite">
            <strong>{__('Error:')} </strong>
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
                  aria-label="Height in feet"
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
                  aria-label="Height in inches"
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
                  aria-label="Height in centimeters"
                />
                <span aria-label="unit">{getUnitLabel('height')}</span>
              </div>
            )}
            {formState.height < 100 && (
              <div className="form-error" role="alert">
                {__('Height must be between 100cm and 300cm')}
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
                aria-label={`Weight in ${getUnitLabel('weight')}`}
              />
              <span aria-label="unit">{getUnitLabel('weight')}</span>
            </div>
            {formState.weight < 30 && (
              <div className="form-error" role="alert">
                {__('Weight must be between 30kg and 300kg')}
              </div>
            )}
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
          feature="physical"
          isLoading={isSaving}
          disabled={isSaving}
        >
          {__('Save Changes')}
        </Button>
      </form>
    </Section>
  );
}; 