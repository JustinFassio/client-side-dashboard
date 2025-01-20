type CacheEntry<T> = {
    data: T;
    timestamp: number;
};

export class WorkoutCache {
    private cache: Map<string, CacheEntry<any>> = new Map();
    private readonly TTL = 5 * 60 * 1000; // 5 minutes in milliseconds

    async getOrFetch<T>(
        key: string,
        fetchFn: () => Promise<T>
    ): Promise<T> {
        const cached = this.cache.get(key);
        const now = Date.now();

        if (cached && now - cached.timestamp < this.TTL) {
            return cached.data;
        }

        const data = await fetchFn();
        this.cache.set(key, { data, timestamp: now });
        return data;
    }

    invalidate(key: string): void {
        this.cache.delete(key);
    }

    clear(): void {
        this.cache.clear();
    }
} 