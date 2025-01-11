import { ApiResponse } from '../types/api';
import { FeatureContext as _FeatureContext } from '../contracts/Feature';

export class ApiClient {
    private static instance: ApiClient;
    private cache = new Map<string, { data: any; timestamp: number }>();
    private context: _FeatureContext;

    private constructor(context: _FeatureContext) {
        this.context = context;
    }

    static getInstance(context: _FeatureContext): ApiClient {
        if (!this.instance) {
            this.instance = new ApiClient(context);
        }
        return this.instance;
    }

    private isCacheValid(timestamp: number, maxAge: number = 60000): boolean {
        return Date.now() - timestamp < maxAge;
    }

    private normalizeUrl(baseUrl: string, endpoint: string): string {
        const cleanBase = baseUrl.replace(/\/+$/, '');
        const cleanEndpoint = endpoint.replace(/^\/+/, '');
        return `${cleanBase}/${cleanEndpoint}`;
    }

    async fetch<T>(endpoint: string): Promise<ApiResponse<T>> {
        const url = this.normalizeUrl(this.context.apiUrl, endpoint);

        try {
            const response = await fetch(url, {
                headers: {
                    'X-WP-Nonce': this.context.nonce
                }
            });

            if (!response.ok) {
                return {
                    data: null,
                    error: {
                        code: 'api_error',
                        message: response.statusText,
                        status: response.status
                    }
                };
            }

            const data = await response.json();
            return { data, error: null };
        } catch (error) {
            return {
                data: null,
                error: {
                    code: 'network_error',
                    message: error instanceof Error ? error.message : 'Unknown error',
                    status: 500
                }
            };
        }
    }

    async fetchWithCache<T>(endpoint: string, maxAge: number = 60000): Promise<ApiResponse<T>> {
        const cached = this.cache.get(endpoint);
        if (cached && this.isCacheValid(cached.timestamp, maxAge)) {
            return { data: cached.data, error: null };
        }

        const response = await this.fetch<T>(endpoint);
        if (response.data) {
            this.cache.set(endpoint, {
                data: response.data,
                timestamp: Date.now()
            });
        }
        return response;
    }

    async fetchWithRetry<T>(endpoint: string, retries: number = 3): Promise<ApiResponse<T>> {
        for (let i = 0; i < retries; i++) {
            const response = await this.fetch<T>(endpoint);
            if (response.data || i === retries - 1) {
                return response;
            }
            await new Promise(resolve => setTimeout(resolve, 1000 * Math.pow(2, i)));
        }
        return {
            data: null,
            error: {
                code: 'max_retries_exceeded',
                message: 'Maximum retry attempts exceeded',
                status: 500
            }
        };
    }
} 