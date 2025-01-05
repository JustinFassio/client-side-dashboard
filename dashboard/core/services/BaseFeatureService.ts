import { FeatureContext } from '../../contracts/Feature';

export interface FetchOptions {
    method?: 'GET' | 'POST' | 'PUT' | 'DELETE';
    body?: any;
    headers?: Record<string, string>;
    cache?: RequestCache;
}

export class BaseFeatureService {
    protected context: FeatureContext;
    protected featureId: string;
    private cache: Map<string, { data: any; timestamp: number }> = new Map();
    private readonly CACHE_TTL = 5 * 60 * 1000; // 5 minutes

    constructor(context: FeatureContext, featureId: string) {
        this.context = context;
        this.featureId = featureId;
    }

    protected async fetchWithCache<T>(endpoint: string, options: FetchOptions = {}): Promise<T> {
        const cacheKey = `${options.method || 'GET'}:${endpoint}`;
        const cached = this.cache.get(cacheKey);

        if (cached && Date.now() - cached.timestamp < this.CACHE_TTL) {
            if (this.context.debug) {
                console.log(`[${this.featureId}] Cache hit for ${endpoint}`);
            }
            return cached.data as T;
        }

        const response = await this.fetch<T>(endpoint, options);
        
        this.cache.set(cacheKey, {
            data: response,
            timestamp: Date.now()
        });

        return response;
    }

    protected async fetch<T>(endpoint: string, options: FetchOptions = {}): Promise<T> {
        const url = `${this.context.apiUrl}/custom/v1/${endpoint}`;
        
        if (this.context.debug) {
            console.log(`[${this.featureId}] Fetching ${url}`, options);
        }

        try {
            const response = await fetch(url, {
                method: options.method || 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.context.nonce,
                    ...options.headers
                },
                body: options.body ? JSON.stringify(options.body) : undefined,
                cache: options.cache
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (this.context.debug) {
                console.log(`[${this.featureId}] Response:`, data);
            }

            return data as T;
        } catch (error) {
            console.error(`[${this.featureId}] API error:`, error);
            throw error;
        }
    }

    protected clearCache(): void {
        this.cache.clear();
    }

    protected invalidateCache(endpoint: string, method: string = 'GET'): void {
        const cacheKey = `${method}:${endpoint}`;
        this.cache.delete(cacheKey);
    }
} 