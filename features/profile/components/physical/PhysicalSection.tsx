import React, { useEffect, useState } from 'react';
import { Section } from '../Section';
import { MeasurementForm } from './MeasurementForm';
import { HistoryView } from './HistoryView';
import { physicalApi } from '../../api/physical';
import { PhysicalData } from '../../types/physical';
import * as styles from './PhysicalSection.module.css';

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
    error
}) => {
    const [physicalData, setPhysicalData] = useState<PhysicalData | null>(null);
    const [loading, setLoading] = useState(true);
    const [loadError, setLoadError] = useState<string | null>(null);

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
            setLoading(false);
        }
    };

    const handleUpdate = async (data: PhysicalData) => {
        setLoading(true);
        try {
            console.log('Updating physical data:', data);
            const updatedData = await physicalApi.updatePhysicalData(userId, data);
            console.log('Update successful:', updatedData);
            setPhysicalData(updatedData);
            setError(null);
        } catch (err) {
            console.error('Update failed:', err);
            setError(err instanceof Error ? err.message : 'Failed to update physical data');
        } finally {
            setLoading(false);
        }
    };

    if (loading) return (
        <Section title="Physical Information">
            <div className={styles.loading} role="status" aria-live="polite">
                Loading physical data...
            </div>
        </Section>
    );

    if (loadError) return (
        <Section title="Physical Information">
            <div className={styles.error} role="alert">
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
                    {error && (
                        <div className="section-error">
                            <p>{error}</p>
                        </div>
                    )}
                </>
            )}
        </Section>
    );
}; 