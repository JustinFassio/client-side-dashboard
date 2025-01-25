import React, { useEffect, useCallback } from 'react';
import { useEquipment } from '../contexts/equipment-context';
import { EquipmentListWidget } from './widgets/EquipmentListWidget';
import { EquipmentSetWidget } from './widgets/EquipmentSetWidget';
import { WorkoutZoneWidget } from './widgets/WorkoutZoneWidget';
import { EquipmentRecommendationsWidget } from './widgets/EquipmentRecommendationsWidget';
import { LoadingSpinner } from '../../../dashboard/components/LoadingSpinner';
import { ErrorMessage } from '../../../dashboard/components/ErrorMessage';
import './styles.css';

interface EquipmentManagerProps {
    _userId: number;
    _userGoals: string[];
    _fitnessLevel: string;
}

export const EquipmentManager: React.FC<EquipmentManagerProps> = ({
    _userId,
    _userGoals,
    _fitnessLevel
}) => {
    const { equipment, equipmentSets, workoutZones, loading, error, actions } = useEquipment();

    const loadData = useCallback(async () => {
        await Promise.all([
            actions.fetchEquipment(),
            actions.fetchEquipmentSets(),
            actions.fetchWorkoutZones()
        ]);
    }, [actions.fetchEquipment, actions.fetchEquipmentSets, actions.fetchWorkoutZones]);

    useEffect(() => {
        loadData();
    }, [loadData]);

    if (loading) {
        return <LoadingSpinner message="Loading equipment data..." />;
    }

    if (error) {
        return (
            <ErrorMessage
                error={error}
                onRetry={actions.clearError}
            />
        );
    }

    return (
        <div className="equipment-manager">
            <header className="equipment-header">
                <h1>Equipment Manager</h1>
                <p>Manage your workout equipment, sets, and zones</p>
            </header>

            <div className="equipment-grid">
                <EquipmentListWidget
                    equipment={equipment}
                    onAdd={actions.addEquipment}
                    onUpdate={actions.updateEquipment}
                    onDelete={actions.deleteEquipment}
                    className="equipment-widget"
                />

                <EquipmentSetWidget
                    equipmentSets={equipmentSets}
                    availableEquipment={equipment}
                    className="equipment-widget"
                />

                <WorkoutZoneWidget
                    workoutZones={workoutZones}
                    availableEquipment={equipment}
                    className="equipment-widget"
                />
            </div>

            <EquipmentRecommendationsWidget
                equipment={equipment}
                userGoals={_userGoals}
                fitnessLevel={_fitnessLevel}
                className="equipment-widget"
            />
        </div>
    );
}; 