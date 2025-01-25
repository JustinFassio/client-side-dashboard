import { createElement, useState } from '@wordpress/element';
import { PlusCircle, XCircle } from 'lucide-react';
import type { TrackerProps, TrackerField } from '../InjuryTracker/types';
import { Button } from '../../../../dashboard/components/Button';
import './styles.css';

interface TrackedItem {
    id: string;
    name: string;
    isCustom?: boolean;
    [key: string]: any;
}

export function Tracker<T extends TrackedItem>({
    items,
    onAdd,
    onUpdate,
    onRemove,
    title,
    description,
    fields,
    predefinedItems,
    className = ''
}: TrackerProps<T>) {
    const [newItemValue, setNewItemValue] = useState('');
    const [selectedPredefined, setSelectedPredefined] = useState('');

    const handleAdd = (value: string, isCustom: boolean = false) => {
        if (!isCustom) {
            const predefinedItem = predefinedItems?.find(item => item.value === value);
            onAdd({ 
                id: `item_${Date.now()}`,
                name: predefinedItem?.label || '',
                isCustom: false
            } as Partial<T>);
        } else {
            onAdd({ 
                id: `item_${Date.now()}`,
                name: value,
                isCustom: true
            } as Partial<T>);
        }
        setNewItemValue('');
        setSelectedPredefined('');
    };

    const renderField = (item: T, field: TrackerField<T>) => {
        const value = item[field.key] as string;
        
        if (field.key === 'name' && !item.isCustom) {
            return null;
        }
        
        switch (field.type) {
            case 'textarea':
                return (
                    <textarea
                        value={value}
                        onChange={(e) => onUpdate(item.id, { [field.key]: e.target.value } as Partial<T>)}
                        placeholder={field.placeholder}
                        className="tracker__input tracker__textarea"
                    />
                );
            case 'select':
                return (
                    <select
                        value={value}
                        onChange={(e) => onUpdate(item.id, { [field.key]: e.target.value } as Partial<T>)}
                        className="tracker__input tracker__select"
                    >
                        {field.options?.map(option => (
                            <option key={option.value} value={option.value} className="tracker__select-option">
                                {option.label}
                            </option>
                        ))}
                    </select>
                );
            default:
                return (
                    <input
                        type="text"
                        value={value}
                        onChange={(e) => onUpdate(item.id, { [field.key]: e.target.value } as Partial<T>)}
                        placeholder={field.placeholder}
                        className="tracker__input"
                    />
                );
        }
    };

    return (
        <div className={`form-section tracker ${className}`}>
            <div className="tracker__header">
                <h2 className="tracker__title">{title}</h2>
                <p className="tracker__description">{description}</p>
            </div>
            
            {predefinedItems && (
                <div className="tracker__form-group">
                    <label className="tracker__label">Select Predefined Item</label>
                    <select
                        value={selectedPredefined}
                        onChange={(e) => {
                            setSelectedPredefined(e.target.value);
                            if (e.target.value) handleAdd(e.target.value, false);
                        }}
                        className="tracker__input tracker__select"
                    >
                        <option value="" className="tracker__select-option">Select an item...</option>
                        {predefinedItems.map(item => (
                            <option key={item.value} value={item.value} className="tracker__select-option">
                                {item.label}
                            </option>
                        ))}
                    </select>
                </div>
            )}

            <div className="tracker__form-group">
                <label className="tracker__label">Add Custom Item</label>
                <div className="tracker__input-group">
                    <input
                        type="text"
                        value={newItemValue}
                        onChange={(e) => setNewItemValue(e.target.value)}
                        placeholder="Enter custom item..."
                        className="tracker__input"
                    />
                    <Button
                        variant="secondary"
                        feature="profile"
                        onClick={() => newItemValue && handleAdd(newItemValue, true)}
                        disabled={!newItemValue}
                    >
                        <PlusCircle size={16} />
                        Add
                    </Button>
                </div>
            </div>

            {items.length > 0 && (
                <div className="tracker__list">
                    {items.map(item => (
                        <div key={item.id} className="tracker__item">
                            <div className="tracker__item-header">
                                <h5 className="tracker__item-title">{item.name}</h5>
                                <Button
                                    variant="secondary"
                                    feature="profile"
                                    onClick={() => onRemove(item.id)}
                                    aria-label="Remove item"
                                >
                                    <XCircle size={16} />
                                </Button>
                            </div>
                            {fields.map(field => (
                                <div key={String(field.key)} className="tracker__form-group">
                                    <label className="tracker__label">{field.label}</label>
                                    {renderField(item, field)}
                                </div>
                            ))}
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
} 