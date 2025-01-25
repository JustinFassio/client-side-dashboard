import React, { useState } from 'react';
import { Equipment, WorkoutZone } from '../../types/equipment-types';

interface WorkoutZoneWidgetProps {
    workoutZones: WorkoutZone[];
    availableEquipment: Equipment[];
    className?: string;
}

interface WorkoutZoneFormData {
    name: string;
    equipmentIds: string[];
    environment: 'home' | 'gym' | 'outdoor';
}

const defaultFormData: WorkoutZoneFormData = {
    name: '',
    equipmentIds: [],
    environment: 'home'
};

export const WorkoutZoneWidget: React.FC<WorkoutZoneWidgetProps> = ({
    workoutZones,
    availableEquipment,
    className
}) => {
    const [showForm, setShowForm] = useState(false);
    const [formData, setFormData] = useState<WorkoutZoneFormData>(defaultFormData);
    const [editingId, setEditingId] = useState<string | null>(null);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        // TODO: Implement zone creation/update logic
        setFormData(defaultFormData);
        setShowForm(false);
        setEditingId(null);
    };

    const handleCancel = () => {
        setFormData(defaultFormData);
        setShowForm(false);
        setEditingId(null);
    };

    const getEquipmentName = (id: string) => {
        const equipment = availableEquipment.find(e => e.id === id);
        return equipment?.name || 'Unknown Equipment';
    };

    return (
        <div className={`workout-zone-widget ${className || ''}`}>
            <div className="widget-header">
                <h2>Workout Zones</h2>
                <button
                    onClick={() => setShowForm(true)}
                    className="button button-primary"
                >
                    Create Zone
                </button>
            </div>

            {showForm && (
                <form onSubmit={handleSubmit} className="workout-zone-form">
                    <div className="form-group">
                        <label htmlFor="name">Zone Name</label>
                        <input
                            type="text"
                            id="name"
                            value={formData.name}
                            onChange={e => setFormData({ ...formData, name: e.target.value })}
                            required
                        />
                    </div>

                    <div className="form-group">
                        <label htmlFor="environment">Environment</label>
                        <select
                            id="environment"
                            value={formData.environment}
                            onChange={e => setFormData({ ...formData, environment: e.target.value as WorkoutZone['environment'] })}
                            required
                        >
                            <option value="home">Home</option>
                            <option value="gym">Gym</option>
                            <option value="outdoor">Outdoor</option>
                        </select>
                    </div>

                    <div className="form-group">
                        <label>Available Equipment</label>
                        <div className="equipment-selection">
                            {availableEquipment.map(equipment => (
                                <label key={equipment.id} className="checkbox-label">
                                    <input
                                        type="checkbox"
                                        checked={formData.equipmentIds.includes(equipment.id)}
                                        onChange={e => {
                                            const newIds = e.target.checked
                                                ? [...formData.equipmentIds, equipment.id]
                                                : formData.equipmentIds.filter(id => id !== equipment.id);
                                            setFormData({ ...formData, equipmentIds: newIds });
                                        }}
                                    />
                                    {equipment.name}
                                </label>
                            ))}
                        </div>
                    </div>

                    <div className="form-actions">
                        <button type="submit" className="button button-primary">
                            {editingId ? 'Update' : 'Create'} Zone
                        </button>
                        <button
                            type="button"
                            onClick={handleCancel}
                            className="button button-secondary"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            )}

            <div className="workout-zones">
                {workoutZones.map(zone => (
                    <div key={zone.id} className="workout-zone">
                        <div className="workout-zone-header">
                            <h3>{zone.name}</h3>
                            <span className={`workout-zone-environment environment-${zone.environment}`}>
                                {zone.environment}
                            </span>
                            <div className="zone-actions">
                                <button className="button button-secondary">Edit</button>
                                <button className="button button-danger">Delete</button>
                            </div>
                        </div>

                        <div className="workout-zone-equipment">
                            {zone.equipmentIds.map(id => (
                                <span key={id} className="workout-zone-equipment-item">
                                    {getEquipmentName(id)}
                                </span>
                            ))}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}; 