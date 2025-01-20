import { performance } from 'perf_hooks';

export interface PerformanceMetric {
    operation: string;
    duration: number;
    timestamp: number;
}

export class PerformanceMonitor {
    private metrics: PerformanceMetric[] = [];

    async measure<T>(operation: string, fn: () => Promise<T>): Promise<T> {
        const start = performance.now();
        try {
            const result = await fn();
            const duration = performance.now() - start;
            this.metrics.push({
                operation,
                duration,
                timestamp: Date.now()
            });
            return result;
        } catch (error) {
            const duration = performance.now() - start;
            this.metrics.push({
                operation: `${operation} (error)`,
                duration,
                timestamp: Date.now()
            });
            throw error;
        }
    }

    getMetrics(): PerformanceMetric[] {
        return this.metrics;
    }

    clearMetrics(): void {
        this.metrics = [];
    }

    getAverageTime(operation: string): number {
        const relevantMetrics = this.metrics.filter(m => m.operation === operation);
        if (relevantMetrics.length === 0) return 0;
        
        const total = relevantMetrics.reduce((sum, metric) => sum + metric.duration, 0);
        return total / relevantMetrics.length;
    }

    getPercentile(operation: string, percentile: number): number {
        const relevantMetrics = this.metrics
            .filter(m => m.operation === operation)
            .map(m => m.duration)
            .sort((a, b) => a - b);

        if (relevantMetrics.length === 0) return 0;

        const index = Math.ceil((percentile / 100) * relevantMetrics.length) - 1;
        return relevantMetrics[index];
    }
}

export const performanceMonitor = new PerformanceMonitor();

// Performance thresholds in milliseconds
export const THRESHOLDS = {
    API_RESPONSE: 300,
    CACHE_RESPONSE: 50,
    DB_QUERY: 100,
    RENDER_TIME: 100
}; 