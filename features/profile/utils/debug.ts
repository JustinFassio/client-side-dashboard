import { Config } from '@dashboard/core/config';
import { ProfileError } from '../services/ProfileService';

interface RequestDebugInfo {
    url: string;
    method: string;
    headers: Record<string, string>;
    body?: any;
    timestamp: string;
}

interface ResponseDebugInfo {
    status: number;
    statusText: string;
    headers: Record<string, string>;
    body?: any;
    timestamp: string;
}

export class ProfileDebug {
    private static debugStorage = new Map<string, any>();

    /**
     * Store debug information
     */
    private static store(key: string, value: any): void {
        this.debugStorage.set(key, value);
        Config.log(`Debug info stored for ${key}`, 'profile-debug');
    }

    /**
     * Get stored debug information
     */
    static getDebugInfo(): Record<string, any> {
        return Object.fromEntries(this.debugStorage);
    }

    /**
     * Clear debug storage
     */
    static clearDebugInfo(): void {
        this.debugStorage.clear();
        Config.log('Debug storage cleared', 'profile-debug');
    }

    /**
     * Log request details
     */
    static logRequest(request: RequestDebugInfo): void {
        this.store(`request-${Date.now()}`, request);
        Config.log(`Request details: ${JSON.stringify(request)}`, 'profile-debug');
    }

    /**
     * Log response details
     */
    static logResponse(response: ResponseDebugInfo): void {
        this.store(`response-${Date.now()}`, response);
        Config.log(`Response details: ${JSON.stringify(response)}`, 'profile-debug');
    }

    /**
     * Test the API connection with detailed logging
     */
    static async testApiConnection(): Promise<boolean> {
        const baseUrl = window.athleteDashboardData.apiUrl.replace(/\/?$/, '');
        const testUrl = `${baseUrl}/athlete-dashboard/v1/profile/test`;

        const requestInfo: RequestDebugInfo = {
            url: testUrl,
            method: 'GET',
            headers: {
                'X-WP-Nonce': window.athleteDashboardData.nonce
            },
            timestamp: new Date().toISOString()
        };

        try {
            this.logRequest(requestInfo);

            const response = await fetch(testUrl, {
                headers: requestInfo.headers
            });

            const responseBody = await response.json();
            const responseInfo: ResponseDebugInfo = {
                status: response.status,
                statusText: response.statusText,
                headers: Object.fromEntries(response.headers.entries()),
                body: responseBody,
                timestamp: new Date().toISOString()
            };

            this.logResponse(responseInfo);
            return response.ok;
        } catch (error) {
            this.logError('API test failed', error);
            return false;
        }
    }

    /**
     * Log API configuration with validation
     */
    static logApiConfig(): void {
        const config = {
            baseUrl: window.athleteDashboardData.apiUrl,
            nonce: window.athleteDashboardData.nonce ? 'present' : 'missing',
            userId: window.athleteDashboardData.userId,
            environment: window.athleteDashboardData.environment,
            validation: {
                hasValidBaseUrl: this.validateBaseUrl(window.athleteDashboardData.apiUrl),
                hasValidNonce: this.validateNonce(window.athleteDashboardData.nonce),
                isUserLoggedIn: !!window.athleteDashboardData.userId
            }
        };

        this.store('api-config', config);
        Config.log(`API configuration: ${JSON.stringify(config)}`, 'profile-debug');
    }

    /**
     * Validate base URL
     */
    private static validateBaseUrl(url?: string): boolean {
        if (!url) return false;
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }

    /**
     * Validate nonce
     */
    private static validateNonce(nonce?: string): boolean {
        return typeof nonce === 'string' && nonce.length > 0;
    }

    /**
     * Log error with context
     */
    static logError(message: string, error: any): void {
        const errorInfo = {
            message,
            timestamp: new Date().toISOString(),
            error: error instanceof ProfileError ? {
                name: error.name,
                message: error.message,
                code: error.code,
                status: error.status
            } : error.toString(),
            context: {
                url: window.location.href,
                userAgent: navigator.userAgent,
                apiConfig: this.getDebugInfo()['api-config']
            }
        };

        this.store(`error-${Date.now()}`, errorInfo);
        Config.log(`Error logged: ${JSON.stringify(errorInfo)}`, 'profile-debug');
    }

    /**
     * Get full debug report
     */
    static async generateDebugReport(): Promise<string> {
        const debugData = {
            timestamp: new Date().toISOString(),
            environment: window.athleteDashboardData.environment,
            apiConnection: await this.testApiConnection(),
            debugStorage: this.getDebugInfo(),
            browserInfo: {
                userAgent: navigator.userAgent,
                language: navigator.language,
                platform: navigator.platform
            }
        };

        return JSON.stringify(debugData, null, 2);
    }
} 