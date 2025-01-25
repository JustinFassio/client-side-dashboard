import { useCallback } from '@wordpress/element';
import { Injury, InjuryTrackerProps, PREDEFINED_INJURIES } from './types';
import { Tracker } from '../Tracker';
import { Button } from '../../../../dashboard/components/Button';
import * as styles from './InjuryTracker.module.css';

export const InjuryTracker = ({
    injuries,
    onChange,
    onSave,
    isSaving,
    error,
    className = ''
}: InjuryTrackerProps & {
    onSave: () => Promise<void>;
    isSaving?: boolean;
    error?: string;
}) => {
    const handleAdd = useCallback((item: Partial<Injury>) => {
        const newInjury: Injury = {
            id: `injury_${Date.now()}`,
            name: item.name || '',
            details: '',
            date: new Date().toISOString(),
            type: '',
            description: '',
            isCustom: true,
            status: 'active',
            severity: 'medium'
        };
        onChange([...injuries, newInjury]);
    }, [injuries, onChange]);

    const handleUpdate = useCallback((id: string, updates: Partial<Injury>) => {
        const updatedInjuries = injuries.map(injury =>
            injury.id === id ? { ...injury, ...updates } : injury
        );
        onChange(updatedInjuries);
    }, [injuries, onChange]);

    const handleRemove = useCallback((id: string) => {
        onChange(injuries.filter(injury => injury.id !== id));
    }, [injuries, onChange]);

    return (
        <div className={styles.section}>
            <Tracker<Injury>
                items={injuries}
                onAdd={handleAdd}
                onUpdate={handleUpdate}
                onRemove={handleRemove}
                title="Injury Tracker"
                description="Track and manage your injuries to optimize your training."
                fields={[
                    {
                        label: 'Details',
                        key: 'details',
                        type: 'textarea',
                        placeholder: 'Add details about this injury...'
                    },
                    {
                        label: 'Severity',
                        key: 'severity',
                        type: 'select',
                        options: [
                            { value: 'low', label: 'Low' },
                            { value: 'medium', label: 'Medium' },
                            { value: 'high', label: 'High' }
                        ]
                    },
                    {
                        label: 'Status',
                        key: 'status',
                        type: 'select',
                        options: [
                            { value: 'active', label: 'Active' },
                            { value: 'recovered', label: 'Recovered' }
                        ]
                    }
                ]}
                predefinedItems={PREDEFINED_INJURIES}
                className={className}
            />

            {error && (
                <div className={styles.error} role="alert">
                    <p>{error}</p>
                </div>
            )}

            <div className={styles.actions}>
                <Button
                    onClick={onSave}
                    disabled={isSaving}
                    isLoading={isSaving}
                    feature="physical"
                >
                    Save Injury Information
                </Button>
            </div>
        </div>
    );
}; 