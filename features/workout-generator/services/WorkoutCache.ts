interface CacheEntry<T> {
    data: T;
    timestamp: number;
}

interface CacheConfig {
    ttl: number; // Time to live in milliseconds
    maxSize: number; // Maximum number of entries
}

export class WorkoutCache {
    private cache: Map<string, CacheEntry<any>>;
    private config: CacheConfig;

    constructor(config: Partial<CacheConfig> = {}) {
        this.cache = new Map();
        this.config = {
            ttl: config.ttl || 5 * 60 * 1000, // 5 minutes default
            maxSize: config.maxSize || 100 // 100 entries default
        };
    }

    async getOrFetch<T>(
        key: string, 
        fetchFn: () => Promise<T>
    ): Promise<T> {
        // Check if cached and not expired
        const cached = this.cache.get(key);
        if (cached && !this.isExpired(cached)) {
            return cached.data;
        }

        // Fetch new data
        const data = await fetchFn();
        
        // Cache the result
        this.set(key, data);
        
        return data;
    }

    set<T>(key: string, data: T): void {
        // Ensure we don't exceed max size
        if (this.cache.size >= this.config.maxSize) {
            this.evictOldest();
        }

        this.cache.set(key, {
            data,
            timestamp: Date.now()
        });
    }

    get<T>(key: string): T | undefined {
        const entry = this.cache.get(key);
        
        if (!entry || this.isExpired(entry)) {
            this.cache.delete(key);
            return undefined;
        }

        return entry.data;
    }

    invalidate(key: string): void {
        this.cache.delete(key);
    }

    clear(): void {
        this.cache.clear();
    }

    private isExpired(entry: CacheEntry<any>): boolean {
        return Date.now() - entry.timestamp > this.config.ttl;
    }

    private evictOldest(): void {
        let oldestKey: string | undefined;
        let oldestTimestamp = Infinity;

        for (const [key, entry] of this.cache.entries()) {
            if (entry.timestamp < oldestTimestamp) {
                oldestTimestamp = entry.timestamp;
                oldestKey = key;
            }
        }

        if (oldestKey) {
            this.cache.delete(oldestKey);
        }
    }
} 