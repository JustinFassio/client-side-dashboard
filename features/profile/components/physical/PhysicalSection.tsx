import React, { useState, useEffect } from 'react';
import { MeasurementForm } from './MeasurementForm';
import { HistoryView } from './HistoryView';
import { physicalApi } from '../../api/physical';
import { PhysicalData } from '../../types';
import * as styles from './PhysicalSection.module.css';

interface PhysicalSectionProps {
  userId: number;
}

export const PhysicalSection: React.FC<PhysicalSectionProps> = ({ userId }) => {
  const [physicalData, setPhysicalData] = useState<PhysicalData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const loadPhysicalData = async () => {
    try {
      setLoading(true);
      const data = await physicalApi.getPhysicalData(userId);
      setPhysicalData(data);
    } catch (e) {
      setError('Failed to load physical data');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (userId) loadPhysicalData();
  }, [userId]);

  const handleUpdate = async (updatedData: PhysicalData) => {
    try {
      const data = await physicalApi.updatePhysicalData(userId, updatedData);
      setPhysicalData(data);
      setError(null);
    } catch (e: any) {
      setError(e.message || 'Failed to update data');
    }
  };

  if (loading) return (
    <div className={styles.loading} role="status" aria-live="polite">
      Loading physical data...
    </div>
  );

  if (error) return (
    <div className={styles.error} role="alert">
      Error: {error}
    </div>
  );

  return (
    <main className={styles['physical-section']} aria-label="Physical Measurements">
      {physicalData && (
        <>
          <MeasurementForm 
            initialData={physicalData}
            onUpdate={handleUpdate}
          />

          {physicalData.preferences?.trackHistory && (
            <HistoryView userId={userId} />
          )}
        </>
      )}
    </main>
  );
}; 