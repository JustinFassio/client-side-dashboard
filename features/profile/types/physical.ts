/**
 * Physical measurement data interface
 */
export interface PhysicalData {
    height: number;
    weight: number;
    chest?: number;
    waist?: number;
    hips?: number;
    units: 'metric' | 'imperial';
    preferences: {
        trackHistory: boolean;
        showMetricAndImperial: boolean;
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