import React, { useState } from 'react';
import { PhysicalMetricFieldProps, DEFAULT_UNITS } from '../../types/physical-metrics';
import { formatDate } from '@dashboard/utils/date';

export const PhysicalMetricField: React.FC<PhysicalMetricFieldProps> = ({
    metricId,
    metric,
    label,
    onUpdate,
    isLoading,
    error
}) => {
    const [isEditing, setIsEditing] = useState(false);
    const [value, setValue] = useState(metric.value.toString());
    const [unit, setUnit] = useState(metric.unit || DEFAULT_UNITS[metricId as keyof typeof DEFAULT_UNITS]);

    const handleEdit = () => {
        setIsEditing(true);
        setValue(metric.value.toString());
        setUnit(metric.unit);
    };

    const handleCancel = () => {
        setIsEditing(false);
        setValue(metric.value.toString());
        setUnit(metric.unit);
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        const numericValue = parseFloat(value);
        
        if (isNaN(numericValue)) {
            return;
        }

        try {
            await onUpdate(numericValue, unit);
            setIsEditing(false);
        } catch (err) {
            console.error('Error updating metric:', err);
        }
    };

    return (
        <div className="physical-metric-field">
            <div className="metric-header">
                <label>{label}</label>
                {!isEditing && (
                    <button
                        type="button"
                        className="edit-button"
                        onClick={handleEdit}
                        disabled={isLoading}
                    >
                        Edit
                    </button>
                )}
            </div>

            {isEditing ? (
                <form onSubmit={handleSubmit} className="metric-form">
                    <div className="input-group">
                        <input
                            type="number"
                            value={value}
                            onChange={(e) => setValue(e.target.value)}
                            step="0.1"
                            disabled={isLoading}
                            required
                        />
                        <select
                            value={unit}
                            onChange={(e) => setUnit(e.target.value)}
                            disabled={isLoading}
                        >
                            <option value={DEFAULT_UNITS[metricId as keyof typeof DEFAULT_UNITS]}>
                                {DEFAULT_UNITS[metricId as keyof typeof DEFAULT_UNITS]}
                            </option>
                            {/* Add alternative units if needed */}
                        </select>
                    </div>

                    <div className="button-group">
                        <button
                            type="submit"
                            className="save-button"
                            disabled={isLoading}
                        >
                            {isLoading ? 'Saving...' : 'Save'}
                        </button>
                        <button
                            type="button"
                            className="cancel-button"
                            onClick={handleCancel}
                            disabled={isLoading}
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            ) : (
                <div className="metric-display">
                    <span className="metric-value">
                        {metric.value} {metric.unit}
                    </span>
                    <span className="metric-timestamp">
                        Last updated: {formatDate(metric.timestamp)}
                    </span>
                </div>
            )}

            {error && <div className="error-message">{error}</div>}
        </div>
    );
}; 