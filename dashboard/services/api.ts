import { ApiResponse } from '../types/api';
import { FeatureContext as _FeatureContext } from '../contracts/Feature';

export class ApiClient {
    private static instance: ApiClient;
    private cache = new Map<string, { data: any; timestamp: number }>();
    private context: _FeatureContext;
    private rateLimitDelay = 1000; // 1 second delay between requests to same endpoint

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
        if (this.context.debug) {
            console.log('🔍 Starting URL normalization:', {
                input: { baseUrl, endpoint }
            });
        }

        // Remove trailing slashes from base URL and leading slashes from endpoint
        const cleanBase = baseUrl.replace(/\/+$/, '');
        const cleanEndpoint = endpoint.toString().replace(/^\/+/, '');

        if (this.context.debug) {
            console.log('🧹 Cleaned URL parts:', {
                cleanBase,
                cleanEndpoint
            });
        }

        // If endpoint is just a number, it's likely a user ID - reject it
        if (/^\d+$/.test(cleanEndpoint)) {
            throw new Error('Invalid endpoint: Cannot be just a number');
        }

        // Check if the endpoint already contains the full path
        if (cleanEndpoint.includes('wp-json') || cleanEndpoint.startsWith('http')) {
            const finalUrl = cleanEndpoint.startsWith('http') ? cleanEndpoint : `${cleanBase}/${cleanEndpoint}`;
            
            if (this.context.debug) {
                console.log('🔄 Using provided full URL:', {
                    finalUrl
                });
            }
            return finalUrl;
        }

        // Ensure we have the wp-json prefix
        const apiBase = cleanBase.includes('/wp-json') ? cleanBase : `${cleanBase}/wp-json`;
        
        // Check if namespace exists in either base URL or endpoint
        const namespace = 'athlete-dashboard/v1';
        const hasNamespaceInBase = apiBase.includes(namespace);
        const hasNamespaceInEndpoint = cleanEndpoint.includes(namespace);
        
        // Only add namespace if it's not present in either location
        const fullEndpoint = hasNamespaceInBase || hasNamespaceInEndpoint 
            ? cleanEndpoint 
            : `${namespace}/${cleanEndpoint}`;

        const url = `${apiBase}/${fullEndpoint}`;

        if (this.context.debug) {
            console.log('🏗️ URL Construction Details:', {
                apiBase,
                namespace,
                hasNamespaceInBase,
                hasNamespaceInEndpoint,
                fullEndpoint,
                finalUrl: url
            });
        }

        return url;
    }

    private logApiError(endpoint: string, status: number, message: string): void {
        if (this.context.debug) {
            console.error(`API Error (${status}) for ${endpoint}: ${message}`);
            console.debug('Request context:', {
                apiUrl: this.context.apiUrl,
                endpoint,
                timestamp: new Date().toISOString()
            });
        }
    }

    private async checkRateLimit(endpoint: string): Promise<boolean> {
        const lastCall = this.cache.get(`rateLimit_${endpoint}`)?.timestamp;
        if (lastCall && Date.now() - lastCall < this.rateLimitDelay) {
            this.logApiError(endpoint, 429, 'Rate limit exceeded. Please wait before retrying.');
            return false;
        }
        this.cache.set(`rateLimit_${endpoint}`, { 
            data: null,
            timestamp: Date.now() 
        });
        return true;
    }

    async fetch<T>(endpoint: string): Promise<ApiResponse<T>> {
        if (!(await this.checkRateLimit(endpoint))) {
            return {
                data: null,
                error: {
                    code: 'rate_limit_exceeded',
                    message: 'Too many requests. Please wait before retrying.',
                    status: 429
                }
            };
        }

        const url = this.normalizeUrl(this.context.apiUrl, endpoint);

        try {
            const response = await fetch(url, {
                headers: {
                    'X-WP-Nonce': this.context.nonce
                },
                credentials: 'include'
            });

            if (!response.ok) {
                const errorMessage = response.status === 403 
                    ? 'Authentication failed. Please refresh the page or log in again.'
                    : response.statusText;
                
                this.logApiError(endpoint, response.status, errorMessage);
                
                return {
                    data: null,
                    error: {
                        code: response.status === 403 ? 'auth_error' : 'api_error',
                        message: errorMessage,
                        status: response.status
                    }
                };
            }

            const data = await response.json();
            return { data, error: null };
        } catch (error) {
            const errorMessage = error instanceof Error ? error.message : 'Unknown error';
            this.logApiError(endpoint, 500, errorMessage);
            
            return {
                data: null,
                error: {
                    code: 'network_error',
                    message: errorMessage,
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

    async post<T>(endpoint: string, data: any): Promise<ApiResponse<T>> {
        if (!(await this.checkRateLimit(endpoint))) {
            return {
                data: null,
                error: {
                    code: 'rate_limit_exceeded',
                    message: 'Too many requests. Please wait before retrying.',
                    status: 429
                }
            };
        }

        const url = this.normalizeUrl(this.context.apiUrl, endpoint);

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.context.nonce,
                    'Accept': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                const errorMessage = response.status === 403 
                    ? 'Authentication failed. Please refresh the page or log in again.'
                    : response.statusText;
                
                this.logApiError(endpoint, response.status, errorMessage);
                
                return {
                    data: null,
                    error: {
                        code: response.status === 403 ? 'auth_error' : 'api_error',
                        message: errorMessage,
                        status: response.status
                    }
                };
            }

            const responseData = await response.json();
            return { data: responseData, error: null };
        } catch (error) {
            const errorMessage = error instanceof Error ? error.message : 'Unknown error';
            this.logApiError(endpoint, 500, errorMessage);
            
            return {
                data: null,
                error: {
                    code: 'network_error',
                    message: errorMessage,
                    status: 500
                }
            };
        }
    }
} 