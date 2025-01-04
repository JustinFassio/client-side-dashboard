import React from 'react';
import { PhysicalMetric, PhysicalMetrics } from '../../types/profile';
import { PhysicalMetricField } from './PhysicalMetricField';

export interface PhysicalMetricsDisplayProps {
    metrics: PhysicalMetrics;
    onUpdate: (metricId: keyof PhysicalMetrics, value: number, unit: string) => Promise<void>;
    isLoading?: boolean;
    error?: Error | null;
}

export const PhysicalMetricsDisplay: React.FC<PhysicalMetricsDisplayProps> = ({
    metrics,
    onUpdate,
    isLoading = false,
    error = null
}) => {
    const defaultMetric: PhysicalMetric = {
        type: 'height',
        value: 0,
        unit: '',
        date: new Date().toISOString()
    };

    return (
        <div className="physical-metrics">
            <PhysicalMetricField
                metricId="height"
                metric={metrics.height || { ...defaultMetric, type: 'height' }}
                label="Height"
                onUpdate={(value, unit) => onUpdate('height', value, unit)}
                isLoading={isLoading}
                error={error}
            />
            <PhysicalMetricField
                metricId="weight"
                metric={metrics.weight || { ...defaultMetric, type: 'weight' }}
                label="Weight"
                onUpdate={(value, unit) => onUpdate('weight', value, unit)}
                isLoading={isLoading}
                error={error}
            />
            {metrics.bodyFat && (
                <PhysicalMetricField
                    metricId="bodyFat"
                    metric={metrics.bodyFat}
                    label="Body Fat"
                    onUpdate={(value, unit) => onUpdate('bodyFat', value, unit)}
                    isLoading={isLoading}
                    error={error}
                />
            )}
            {metrics.muscleMass && (
                <PhysicalMetricField
                    metricId="muscleMass"
                    metric={metrics.muscleMass}
                    label="Muscle Mass"
                    onUpdate={(value, unit) => onUpdate('muscleMass', value, unit)}
                    isLoading={isLoading}
                    error={error}
                />
            )}
        </div>
    );
}; 