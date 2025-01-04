import React from 'react';
import { PhysicalMetric } from '../../types/profile';

export interface PhysicalMetricFieldProps {
    metricId: string;
    metric: PhysicalMetric;
    label: string;
    onUpdate: (value: number, unit: string) => Promise<void>;
    isLoading?: boolean;
    error?: Error | null;
}

export const PhysicalMetricField: React.FC<PhysicalMetricFieldProps> = ({
    metricId,
    metric,
    label,
    onUpdate,
    isLoading = false,
    error = null
}) => {
    const [value, setValue] = React.useState(metric?.value?.toString() || '');
    const [unit, setUnit] = React.useState(metric?.unit || '');
    const [isUpdating, setIsUpdating] = React.useState(false);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!value || isUpdating) return;

        setIsUpdating(true);
        try {
            await onUpdate(parseFloat(value), unit);
        } finally {
            setIsUpdating(false);
        }
    };

    return (
        <div className="metric-card">
            <div className="metric-header">
                <span className="metric-label">{label}</span>
                {metric?.date && (
                    <span className="metric-date">
                        Last updated: {new Date(metric.date).toLocaleDateString()}
                    </span>
                )}
            </div>

            <div className="metric-value">
                {metric?.value} <span className="metric-unit">{metric?.unit}</span>
            </div>

            <form className="metric-update-form" onSubmit={handleSubmit}>
                <div className="metric-input-group">
                    <input
                        type="number"
                        className="metric-input"
                        value={value}
                        onChange={(e) => setValue(e.target.value)}
                        placeholder="Enter new value"
                        disabled={isLoading || isUpdating}
                    />
                    <select
                        className="metric-unit-select"
                        value={unit}
                        onChange={(e) => setUnit(e.target.value)}
                        disabled={isLoading || isUpdating}
                    >
                        <option value="kg">kg</option>
                        <option value="lbs">lbs</option>
                        <option value="cm">cm</option>
                        <option value="in">in</option>
                        <option value="%">%</option>
                    </select>
                </div>

                {error && <div className="metric-error">{error.message}</div>}

                <button
                    type="submit"
                    className="metric-update-button"
                    disabled={!value || isLoading || isUpdating}
                >
                    {isUpdating ? 'Updating...' : 'Update'}
                </button>
            </form>
        </div>
    );
}; 