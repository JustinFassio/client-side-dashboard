export interface PhysicalMetric {
    value: number;
    unit: string;
    date: string;
}

export interface PhysicalMetrics {
    height: PhysicalMetric;
    weight: PhysicalMetric;
    bodyFat?: PhysicalMetric;
    muscleMass?: PhysicalMetric;
}

export type MetricKey = keyof PhysicalMetrics;

export interface MetricUpdate {
    value: number;
    unit: string;
    metricKey: MetricKey;
}

export interface PhysicalMetricFieldProps {
    value: number;
    unit: string;
    date: string;
    label: string;
    onUpdate?: (value: number, unit: string) => void;
}

export interface PhysicalMetricsDisplayProps {
    metrics: PhysicalMetrics;
    onUpdate?: (key: MetricKey, value: number, unit: string) => void;
}

export const DEFAULT_UNITS = {
    height: 'cm',
    weight: 'kg',
    bodyFat: '%',
    muscleMass: 'kg'
} as const;

export const METRIC_LABELS = {
    height: 'Height',
    weight: 'Weight',
    bodyFat: 'Body Fat',
    muscleMass: 'Muscle Mass'
} as const; 