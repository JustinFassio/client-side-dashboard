import React, { useState } from 'react';
import { Equipment, EquipmentSet } from '../../types/equipment-types';

interface EquipmentSetWidgetProps {
    equipmentSets: EquipmentSet[];
    availableEquipment: Equipment[];
    className?: string;
}

interface EquipmentSetFormData {
    name: string;
    equipmentIds: string[];
    notes?: string;
}

const defaultFormData: EquipmentSetFormData = {
    name: '',
    equipmentIds: [],
    notes: ''
};

export const EquipmentSetWidget: React.FC<EquipmentSetWidgetProps> = ({
    equipmentSets,
    availableEquipment,
    className
}) => {
    const [showForm, setShowForm] = useState(false);
    const [formData, setFormData] = useState<EquipmentSetFormData>(defaultFormData);
    const [editingId, setEditingId] = useState<string | null>(null);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        // TODO: Implement set creation/update logic
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
        <div className={`equipment-set-widget ${className || ''}`}>
            <div className="widget-header">
                <h2>Equipment Sets</h2>
                <button
                    onClick={() => setShowForm(true)}
                    className="button button-primary"
                >
                    Create Set
                </button>
            </div>

            {showForm && (
                <form onSubmit={handleSubmit} className="equipment-set-form">
                    <div className="form-group">
                        <label htmlFor="name">Set Name</label>
                        <input
                            type="text"
                            id="name"
                            value={formData.name}
                            onChange={e => setFormData({ ...formData, name: e.target.value })}
                            required
                        />
                    </div>

                    <div className="form-group">
                        <label>Equipment</label>
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

                    <div className="form-group">
                        <label htmlFor="notes">Notes</label>
                        <textarea
                            id="notes"
                            value={formData.notes}
                            onChange={e => setFormData({ ...formData, notes: e.target.value })}
                            placeholder="Add notes about this equipment set..."
                        />
                    </div>

                    <div className="form-actions">
                        <button type="submit" className="button button-primary">
                            {editingId ? 'Update' : 'Create'} Set
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

            <div className="equipment-sets">
                {equipmentSets.map(set => (
                    <div key={set.id} className="equipment-set">
                        <div className="equipment-set-header">
                            <h3>{set.name}</h3>
                            <div className="set-actions">
                                <button className="button button-secondary">Edit</button>
                                <button className="button button-danger">Delete</button>
                            </div>
                        </div>

                        <div className="equipment-set-items">
                            {set.equipmentIds.map(id => (
                                <span key={id} className="equipment-set-item">
                                    {getEquipmentName(id)}
                                </span>
                            ))}
                        </div>

                        {set.notes && (
                            <p className="equipment-set-notes">{set.notes}</p>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
}; 