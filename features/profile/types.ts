export interface PhysicalData {
  height: number;
  weight: number;
  chest?: number;
  waist?: number;
  hips?: number;
  units: {
    height: 'cm' | 'ft';
    weight: 'kg' | 'lbs';
    measurements: 'cm' | 'in';
  };
  preferences?: {
    showMetric: boolean;
    trackHistory: boolean;
  };
}

export interface PhysicalHistory extends PhysicalData {
  id: number;
  date: string;
}

export interface PhysicalHistoryResponse {
  items: PhysicalHistory[];
  total: number;
  limit: number;
  offset: number;
  tracking_disabled?: boolean;
} 