import React, { useState } from 'react';
import { PhysicalMetricsDisplayProps, METRIC_LABELS } from '../../types/physical-metrics';
import { PhysicalMetricField } from './PhysicalMetricField';
import './styles.scss';

export const PhysicalMetricsDisplay: React.FC<PhysicalMetricsDisplayProps> = ({
    metrics,
    onUpdate,
    isLoading,
    error
}) => {
    const [activeMetric, setActiveMetric] = useState<string | null>(null);

    const handleUpdate = async (metricId: string, value: number, unit: string) => {
        try {
            await onUpdate(metricId, value, unit);
            setActiveMetric(null);
        } catch (err) {
            console.error('Error updating metric:', err);
        }
    };

    return (
        <div className="physical-metrics-display">
            <h3>Physical Metrics</h3>
            {error && <div className="error-message">{error}</div>}
            
            <div className="metrics-grid">
                {Object.entries(metrics).map(([metricId, metric]) => (
                    <PhysicalMetricField
                        key={metricId}
                        metricId={metricId}
                        metric={metric}
                        label={METRIC_LABELS[metricId as keyof typeof METRIC_LABELS]}
                        onUpdate={(value, unit) => handleUpdate(metricId, value, unit)}
                        isLoading={isLoading && activeMetric === metricId}
                        error={error}
                    />
                ))}
            </div>
        </div>
    );
}; 