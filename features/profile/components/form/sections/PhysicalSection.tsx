import React, { useState, useEffect } from 'react';
import FormField from '../fields/FormField';
import { ProfileData } from '../../../types/profile';
import { FormValidationResult } from '../../../types/validation';

interface PhysicalSectionProps {
    data: Partial<ProfileData>;
    onChange: (name: string, value: any) => void;
    validation?: FormValidationResult;
}

// Unit conversion utilities
const convertHeight = {
    toImperial: (cm: number) => {
        const totalInches = cm / 2.54;
        const feet = Math.floor(totalInches / 12);
        const inches = Math.round(totalInches % 12);
        console.log('Converting height to imperial:', { cm, totalInches, feet, inches });
        return { feet, inches: inches === 12 ? 0 : inches }; // Prevent 12 inches
    },
    toCm: (feet: number, inches: number) => {
        // Validate inches range
        if (inches >= 12) {
            console.warn('Invalid inches value:', inches);
            inches = 11;
        }
        const cm = Number(((feet * 30.48) + (inches * 2.54)).toFixed(1));
        console.log('Converting height to cm:', { feet, inches, cm });
        return cm;
    }
};

const convertWeight = {
    toLbs: (kg: number) => {
        const lbs = Number((kg * 2.205).toFixed(1));
        console.log('Converting weight to lbs:', { kg, lbs });
        return lbs;
    },
    toKg: (lbs: number) => {
        const kg = Number((lbs / 2.205).toFixed(1));
        console.log('Converting weight to kg:', { lbs, kg });
        return kg;
    }
};

// Weight validation ranges
const WEIGHT_RANGES = {
    imperial: { min: 66, max: 440 }, // lbs
    metric: { min: 30, max: 200 }    // kg
};

export const PhysicalSection: React.FC<PhysicalSectionProps> = ({
    data,
    onChange,
    validation
}) => {
    const [unitSystem, setUnitSystem] = useState<'imperial' | 'metric'>(
        data.preferredUnits || 'imperial'
    );
    const [heightFeet, setHeightFeet] = useState(0);
    const [heightInches, setHeightInches] = useState(0);

    // Initialize imperial height values when component mounts or height changes
    useEffect(() => {
        if (data.height && unitSystem === 'imperial') {
            const imperial = convertHeight.toImperial(data.height);
            console.log('Initializing imperial height:', { cm: data.height, ...imperial });
            setHeightFeet(imperial.feet);
            setHeightInches(imperial.inches);
        }
    }, [data.height, unitSystem]);

    // Handle unit system change
    const handleUnitSystemChange = (name: string, value: 'imperial' | 'metric') => {
        setUnitSystem(value);
        onChange('preferredUnits', value);
    };

    // Handle imperial height change
    const handleImperialHeightChange = (field: 'feet' | 'inches', value: number) => {
        console.log('Height field change:', { field, value });
        
        let newFeet = field === 'feet' ? value : heightFeet;
        let newInches = field === 'inches' ? value : heightInches;

        // Validate inches range
        if (newInches >= 12) {
            console.warn('Correcting inches value:', newInches);
            newInches = 11;
        }

        setHeightFeet(newFeet);
        setHeightInches(newInches);
        
        const cm = convertHeight.toCm(newFeet, newInches);
        console.log('Updated height values:', { feet: newFeet, inches: newInches, cm });
        onChange('height', cm);
    };

    return (
        <div className="form-section">
            <h2>Physical Information</h2>
            <p className="form-section__description">
                Update your physical details.
            </p>

            <FormField
                name="preferredUnits"
                label="Preferred Units"
                type="select"
                value={unitSystem}
                onChange={handleUnitSystemChange}
                options={[
                    { value: 'imperial', label: 'Imperial (ft/in, lbs)' },
                    { value: 'metric', label: 'Metric (cm, kg)' }
                ]}
            />

            {unitSystem === 'imperial' ? (
                <>
                    <div className="form-group">
                        <label className="form-label">Height (ft/in)</label>
                        <div className="form-group__row">
                            <FormField
                                name="heightFeet"
                                label=""
                                type="number"
                                value={heightFeet}
                                onChange={(_, value) => handleImperialHeightChange('feet', value)}
                                min={0}
                                max={8}
                                placeholder="Feet"
                            />
                            <FormField
                                name="heightInches"
                                label=""
                                type="number"
                                value={heightInches}
                                onChange={(_, value) => handleImperialHeightChange('inches', value)}
                                min={0}
                                max={11}
                                placeholder="Inches"
                            />
                        </div>
                        {validation?.fieldErrors?.height && (
                            <div className="form-error">
                                {validation.fieldErrors.height.join(', ')}
                            </div>
                        )}
                    </div>

                    <FormField
                        name="weight"
                        label="Weight (lbs)"
                        type="number"
                        value={data.weight ? convertWeight.toLbs(data.weight) : ''}
                        onChange={(name, value) => {
                            const kg = convertWeight.toKg(value);
                            console.log('Weight field change:', { inputLbs: value, convertedKg: kg });
                            onChange('weight', kg);
                        }}
                        validation={validation?.fieldErrors?.weight && {
                            isValid: false,
                            errors: validation.fieldErrors.weight
                        }}
                        min={WEIGHT_RANGES.imperial.min}
                        max={WEIGHT_RANGES.imperial.max}
                        step={0.1}
                    />
                </>
            ) : (
                <>
                    <FormField
                        name="height"
                        label="Height (cm)"
                        type="number"
                        value={data.height}
                        onChange={onChange}
                        validation={validation?.fieldErrors?.height && {
                            isValid: false,
                            errors: validation.fieldErrors.height
                        }}
                        min={50}
                        max={250}
                    />

                    <FormField
                        name="weight"
                        label="Weight (kg)"
                        type="number"
                        value={data.weight}
                        onChange={onChange}
                        validation={validation?.fieldErrors?.weight && {
                            isValid: false,
                            errors: validation.fieldErrors.weight
                        }}
                        min={WEIGHT_RANGES.metric.min}
                        max={WEIGHT_RANGES.metric.max}
                        step={0.1}
                    />
                </>
            )}
            
            <FormField
                name="fitnessLevel"
                label="Fitness Level"
                type="select"
                value={data.fitnessLevel}
                onChange={onChange}
                validation={validation?.fieldErrors?.fitnessLevel && {
                    isValid: false,
                    errors: validation.fieldErrors.fitnessLevel
                }}
                options={[
                    { value: 'beginner', label: 'Beginner' },
                    { value: 'intermediate', label: 'Intermediate' },
                    { value: 'advanced', label: 'Advanced' },
                    { value: 'expert', label: 'Expert' }
                ]}
                required
            />
            
            <FormField
                name="activityLevel"
                label="Activity Level"
                type="select"
                value={data.activityLevel}
                onChange={onChange}
                validation={validation?.fieldErrors?.activityLevel && {
                    isValid: false,
                    errors: validation.fieldErrors.activityLevel
                }}
                options={[
                    { value: 'sedentary', label: 'Sedentary' },
                    { value: 'lightly_active', label: 'Lightly Active' },
                    { value: 'moderately_active', label: 'Moderately Active' },
                    { value: 'very_active', label: 'Very Active' },
                    { value: 'extremely_active', label: 'Extremely Active' }
                ]}
                required
            />
        </div>
    );
};

export default PhysicalSection; 