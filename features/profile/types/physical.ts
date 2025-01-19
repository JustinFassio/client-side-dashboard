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
        height: 'cm' | 'ft';
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
export interface PhysicalHistory extends PhysicalData {
    id: number;
    date: string;
}

export interface PhysicalHistoryResponse {
    items: PhysicalHistory[];
    total: number;
    limit: number;
    offset: number;
}