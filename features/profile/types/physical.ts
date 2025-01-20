/**
 * Physical measurement data interface
 */
export interface PhysicalData {
    height: number;
    heightFeet?: number;
    heightInches?: number;
    weight: number;
    chest?: number;
    waist?: number;
    hips?: number;
    units: {
        height: 'cm' | 'ft' | 'in';
        weight: 'kg' | 'lbs';
        measurements: 'cm' | 'in';
    };
    preferences: {
        showMetric: boolean;
    };
}

/**
 * Physical history entry interface
 */
export interface PhysicalHistory {
    id: number;
    date: string;
    height: number;
    weight: number;
    chest?: number;
    waist?: number;
    hips?: number;
    units_height: 'cm' | 'ft' | 'in';
    units_weight: 'kg' | 'lbs';
    units_measurements: 'cm' | 'in';
}

export interface PhysicalHistoryResponse {
    items: PhysicalHistory[];
    total: number;
    limit: number;
    offset: number;
}

// Add utility types for unit conversions
export type HeightUnit = 'cm' | 'ft' | 'in';
export type WeightUnit = 'kg' | 'lbs';
export type MeasurementUnit = 'cm' | 'in';

export interface UnitConversion {
    fromValue: number;
    fromUnit: HeightUnit | WeightUnit | MeasurementUnit;
    toUnit: HeightUnit | WeightUnit | MeasurementUnit;
}