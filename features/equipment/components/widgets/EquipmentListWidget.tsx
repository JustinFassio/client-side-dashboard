import React, { useState } from 'react';
import { Equipment } from '../../types/equipment-types';

interface EquipmentListWidgetProps {
    equipment: Equipment[];
    onAdd: (equipment: Omit<Equipment, 'id'>) => void;
    onUpdate: (id: string, updates: Partial<Equipment>) => void;
    onDelete: (id: string) => void;
    className?: string;
}

interface EquipmentFormData {
    name: string;
    type: 'machine' | 'free weights' | 'bands' | 'other';
    weightRange?: string;
    quantity?: number;
    description?: string;
}

const defaultFormData: EquipmentFormData = {
    name: '',
    type: 'machine',
    weightRange: '',
    quantity: 1,
    description: ''
};

export const EquipmentListWidget: React.FC<EquipmentListWidgetProps> = ({
    equipment,
    onAdd,
    onUpdate,
    onDelete,
    className
}) => {
    const [showForm, setShowForm] = useState(false);
    const [formData, setFormData] = useState<EquipmentFormData>(defaultFormData);
    const [editingId, setEditingId] = useState<string | null>(null);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingId) {
            onUpdate(editingId, formData);
        } else {
            onAdd(formData);
        }
        setFormData(defaultFormData);
        setShowForm(false);
        setEditingId(null);
    };

    const handleEdit = (equipment: Equipment) => {
        setFormData(equipment);
        setEditingId(equipment.id);
        setShowForm(true);
    };

    const handleCancel = () => {
        setFormData(defaultFormData);
        setShowForm(false);
        setEditingId(null);
    };

    return (
        <div className={`equipment-list-widget ${className || ''}`}>
            <div className="widget-header">
                <h2>Equipment Inventory</h2>
                <button
                    onClick={() => setShowForm(true)}
                    className="button button-primary"
                >
                    Add Equipment
                </button>
            </div>

            {showForm && (
                <form onSubmit={handleSubmit} className="equipment-form">
                    <div className="form-group">
                        <label htmlFor="name">Name</label>
                        <input
                            type="text"
                            id="name"
                            value={formData.name}
                            onChange={e => setFormData({ ...formData, name: e.target.value })}
                            required
                        />
                    </div>

                    <div className="form-group">
                        <label htmlFor="type">Type</label>
                        <select
                            id="type"
                            value={formData.type}
                            onChange={e => setFormData({ ...formData, type: e.target.value as Equipment['type'] })}
                            required
                        >
                            <option value="machine">Machine</option>
                            <option value="free weights">Free Weights</option>
                            <option value="bands">Bands</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div className="form-group">
                        <label htmlFor="weightRange">Weight Range</label>
                        <input
                            type="text"
                            id="weightRange"
                            value={formData.weightRange}
                            onChange={e => setFormData({ ...formData, weightRange: e.target.value })}
                            placeholder="e.g., 10-50 lbs"
                        />
                    </div>

                    <div className="form-group">
                        <label htmlFor="quantity">Quantity</label>
                        <input
                            type="number"
                            id="quantity"
                            value={formData.quantity}
                            onChange={e => setFormData({ ...formData, quantity: parseInt(e.target.value) })}
                            min="1"
                        />
                    </div>

                    <div className="form-group">
                        <label htmlFor="description">Description</label>
                        <textarea
                            id="description"
                            value={formData.description}
                            onChange={e => setFormData({ ...formData, description: e.target.value })}
                            placeholder="Add notes or description..."
                        />
                    </div>

                    <div className="form-actions">
                        <button type="submit" className="button button-primary">
                            {editingId ? 'Update' : 'Add'} Equipment
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

            <div className="equipment-list">
                {equipment.map(item => (
                    <div key={item.id} className="equipment-item">
                        <div className="equipment-item-header">
                            <h3>{item.name}</h3>
                            <span className={`equipment-type type-${item.type.replace(' ', '-')}`}>
                                {item.type}
                            </span>
                        </div>

                        <div className="equipment-details">
                            {item.weightRange && (
                                <span className="detail">
                                    <strong>Weight Range:</strong> {item.weightRange}
                                </span>
                            )}
                            {item.quantity && (
                                <span className="detail">
                                    <strong>Quantity:</strong> {item.quantity}
                                </span>
                            )}
                        </div>

                        {item.description && (
                            <p className="equipment-description">{item.description}</p>
                        )}

                        <div className="equipment-actions">
                            <button
                                onClick={() => handleEdit(item)}
                                className="button button-secondary"
                            >
                                Edit
                            </button>
                            <button
                                onClick={() => onDelete(item.id)}
                                className="button button-danger"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}; 