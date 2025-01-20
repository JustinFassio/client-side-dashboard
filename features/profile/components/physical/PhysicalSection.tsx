import React, { useEffect, useState } from 'react';
import { Section } from '../Section';
import { MeasurementForm } from './MeasurementForm';
import { HistoryView } from './HistoryView';
import { physicalApi } from '../../api/physical';
import { PhysicalData } from '../../types/physical';
import { loading, error } from './PhysicalSection.module.css';

interface PhysicalSectionProps {
    userId: number;
    onSave: () => Promise<void>;
    isSaving?: boolean;
    error?: string;
}

export const PhysicalSection: React.FC<PhysicalSectionProps> = ({
    userId,
    onSave,
    isSaving,
    error: externalError
}) => {
    const [physicalData, setPhysicalData] = useState<PhysicalData | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [loadError, setLoadError] = useState<string | null>(null);
    const [updateError, setUpdateError] = useState<string | null>(null);

    useEffect(() => {
        loadPhysicalData();
    }, [userId]);

    const loadPhysicalData = async () => {
        try {
            const data = await physicalApi.getPhysicalData(userId);
            console.log('Physical data loaded:', data);
            setPhysicalData(data);
            setLoadError(null);
        } catch (err) {
            console.error('Failed to load physical data:', err);
            setLoadError(err instanceof Error ? err.message : 'Failed to load physical data');
        } finally {
            setIsLoading(false);
        }
    };

    const handleUpdate = async (data: PhysicalData) => {
        setIsLoading(true);
        try {
            console.log('Updating physical data:', data);
            const updatedData = await physicalApi.updatePhysicalData(userId, data);
            console.log('Update successful:', updatedData);
            setPhysicalData(updatedData);
            setUpdateError(null);
        } catch (err) {
            console.error('Update failed:', err);
            setUpdateError(err instanceof Error ? err.message : 'Failed to update physical data');
        } finally {
            setIsLoading(false);
        }
    };

    if (isLoading) return (
        <Section title="Physical Information">
            <div className={loading} role="status" aria-live="polite">
                Loading physical data...
            </div>
        </Section>
    );

    if (loadError) return (
        <Section title="Physical Information">
            <div className={error} role="alert">
                Error: {loadError}
            </div>
        </Section>
    );

    return (
        <Section title="Physical Information">
            {physicalData && (
                <>
                    <p className="section-description">
                        Track your physical measurements and monitor your progress over time.
                    </p>
                    <MeasurementForm 
                        initialData={physicalData}
                        onUpdate={handleUpdate}
                    />
                    <HistoryView userId={userId} />
                    {(updateError || externalError) && (
                        <div className={error} role="alert">
                            {updateError || externalError}
                        </div>
                    )}
                </>
            )}
        </Section>
    );
}; 