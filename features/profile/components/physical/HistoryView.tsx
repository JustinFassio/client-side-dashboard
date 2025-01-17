import React, { useState, useEffect } from 'react';
import { physicalApi } from '../../api/physical';
import { PhysicalHistory } from '../../types';
import * as styles from './PhysicalSection.module.css';

interface HistoryViewProps {
  userId: number;
}

interface PaginationState {
  offset: number;
  limit: number;
  total: number;
}

export const HistoryView: React.FC<HistoryViewProps> = ({ userId }) => {
  const [history, setHistory] = useState<PhysicalHistory[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [pagination, setPagination] = useState<PaginationState>({
    offset: 0,
    limit: 10,
    total: 0
  });

  const loadHistory = async () => {
    try {
      setLoading(true);
      const response = await physicalApi.getPhysicalHistory(
        userId,
        pagination.offset,
        pagination.limit
      );
      setHistory(response.items);
      setPagination(prev => ({ ...prev, total: response.total }));
      setError(null);
    } catch (e) {
      setError('Failed to load measurement history');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (userId) loadHistory();
  }, [userId, pagination.offset, pagination.limit]);

  const handleNextPage = () => {
    if (pagination.offset + pagination.limit < pagination.total) {
      setPagination(prev => ({
        ...prev,
        offset: prev.offset + prev.limit
      }));
    }
  };

  const handlePrevPage = () => {
    if (pagination.offset > 0) {
      setPagination(prev => ({
        ...prev,
        offset: Math.max(0, prev.offset - prev.limit)
      }));
    }
  };

  if (loading) return (
    <div className={styles.loading} role="status" aria-live="polite">
      Loading history...
    </div>
  );

  if (error) return (
    <div className={styles.error} role="alert">
      Error: {error}
    </div>
  );

  return (
    <section className={styles['history-view']} aria-labelledby="history-title">
      <h3 id="history-title">Measurement History</h3>

      {history.length === 0 ? (
        <p>No measurement history available.</p>
      ) : (
        <>
          <div className={styles['table-wrapper']} role="region" aria-label="Measurement history table" tabIndex={0}>
            <table className={styles['history-table']}>
              <thead>
                <tr>
                  <th scope="col">Date</th>
                  <th scope="col">Height</th>
                  <th scope="col">Weight</th>
                  <th scope="col">Chest</th>
                  <th scope="col">Waist</th>
                  <th scope="col">Hips</th>
                </tr>
              </thead>
              <tbody>
                {history.map(item => (
                  <tr key={item.id}>
                    <td>{new Date(item.date).toLocaleDateString()}</td>
                    <td>{item.height} {item.units.height}</td>
                    <td>{item.weight} {item.units.weight}</td>
                    <td>{item.chest ? `${item.chest} ${item.units.measurements}` : '-'}</td>
                    <td>{item.waist ? `${item.waist} ${item.units.measurements}` : '-'}</td>
                    <td>{item.hips ? `${item.hips} ${item.units.measurements}` : '-'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          <nav className={styles.pagination} aria-label="History pagination">
            <button
              onClick={handlePrevPage}
              disabled={pagination.offset === 0}
              aria-label="Previous page"
            >
              Previous
            </button>
            <span>
              Showing {pagination.offset + 1} to{' '}
              {Math.min(pagination.offset + pagination.limit, pagination.total)}{' '}
              of {pagination.total}
            </span>
            <button
              onClick={handleNextPage}
              disabled={pagination.offset + pagination.limit >= pagination.total}
              aria-label="Next page"
            >
              Next
            </button>
          </nav>
        </>
      )}
    </section>
  );
}; 