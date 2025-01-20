import React from 'react';
import { render, act } from '@testing-library/react';
import ProfileFeature from '../../components/ProfileFeature';
import { performanceMonitor, THRESHOLDS } from './setupPerformance';

describe('ProfileFeature Performance', () => {
    beforeEach(() => {
        performanceMonitor.clearMetrics();
    });

    it('loads profile data within performance threshold', async () => {
        await performanceMonitor.measure('API_GET_PROFILE', async () => {
            await act(async () => {
                render(<ProfileFeature />);
            });
        });

        const avgTime = performanceMonitor.getAverageTime('API_GET_PROFILE');
        const p95Time = performanceMonitor.getPercentile('API_GET_PROFILE', 95);

        expect(avgTime).toBeLessThan(THRESHOLDS.API_RESPONSE);
        expect(p95Time).toBeLessThan(THRESHOLDS.API_RESPONSE * 1.5);
    });

    it('caches profile data effectively', async () => {
        // First load - from API
        await performanceMonitor.measure('INITIAL_LOAD', async () => {
            await act(async () => {
                render(<ProfileFeature />);
            });
        });

        // Second load - should be from cache
        await performanceMonitor.measure('CACHED_LOAD', async () => {
            await act(async () => {
                render(<ProfileFeature />);
            });
        });

        const initialTime = performanceMonitor.getAverageTime('INITIAL_LOAD');
        const cachedTime = performanceMonitor.getAverageTime('CACHED_LOAD');

        expect(cachedTime).toBeLessThan(THRESHOLDS.CACHE_RESPONSE);
        expect(cachedTime).toBeLessThan(initialTime * 0.5);
    });

    it('renders form updates within performance threshold', async () => {
        const { rerender } = render(<ProfileFeature />);

        await performanceMonitor.measure('FORM_UPDATE', async () => {
            await act(async () => {
                rerender(<ProfileFeature key="updated" />);
            });
        });

        const updateTime = performanceMonitor.getAverageTime('FORM_UPDATE');
        expect(updateTime).toBeLessThan(THRESHOLDS.RENDER_TIME);
    });

    it('handles concurrent operations efficiently', async () => {
        const operations = Array(5).fill(null).map((_, i) => 
            performanceMonitor.measure(`CONCURRENT_OP_${i}`, async () => {
                await act(async () => {
                    render(<ProfileFeature key={i} />);
                });
            })
        );

        await Promise.all(operations);

        const concurrentTimes = Array(5)
            .fill(null)
            .map((_, i) => performanceMonitor.getAverageTime(`CONCURRENT_OP_${i}`));

        const maxTime = Math.max(...concurrentTimes);
        expect(maxTime).toBeLessThan(THRESHOLDS.API_RESPONSE * 2);
    });

    it('maintains performance under load', async () => {
        const iterations = 10;
        for (let i = 0; i < iterations; i++) {
            await performanceMonitor.measure(`LOAD_TEST_${i}`, async () => {
                await act(async () => {
                    render(<ProfileFeature key={i} />);
                });
            });
        }

        const times = Array(iterations)
            .fill(null)
            .map((_, i) => performanceMonitor.getAverageTime(`LOAD_TEST_${i}`));

        const avgTime = times.reduce((a, b) => a + b, 0) / iterations;
        const maxTime = Math.max(...times);

        expect(avgTime).toBeLessThan(THRESHOLDS.API_RESPONSE);
        expect(maxTime).toBeLessThan(THRESHOLDS.API_RESPONSE * 2);
    });
}); 