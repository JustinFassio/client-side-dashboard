import { PhysicalData, PhysicalHistory, PhysicalHistoryResponse } from '../types/physical';

const BASE_URL = '/wp-json/athlete-dashboard/v1/profile/physical';

// Get the WordPress nonce from the page
const getNonce = (): string => {
    console.log('=== Nonce Debug ===');
    console.log('Window athleteDashboardData:', (window as any).athleteDashboardData);
    const dashboardData = (window as any).athleteDashboardData;
    if (!dashboardData?.nonce) {
        console.error('WordPress nonce not found in athleteDashboardData');
        return '';
    }
    console.log('Found nonce:', dashboardData.nonce);
    return dashboardData.nonce;
};

// Common fetch options for authenticated requests
const getAuthOptions = (method: string = 'GET', body?: any): RequestInit => {
    const options: RequestInit = {
        method,
        credentials: 'same-origin' as RequestCredentials,
        headers: {
            'X-WP-Nonce': getNonce(),
            'Content-Type': 'application/json',
        },
        ...(body ? { body: JSON.stringify(body) } : {}),
    };
    console.log('Request options:', options);
    return options;
};

export type PhysicalApi = {
    getPhysicalData(userId: number): Promise<PhysicalData>;
    updatePhysicalData(userId: number, data: PhysicalData): Promise<PhysicalData>;
    getPhysicalHistory(userId: number, offset?: number, limit?: number): Promise<PhysicalHistoryResponse>;
};

export const physicalApi: PhysicalApi = {
    async getPhysicalData(userId: number): Promise<PhysicalData> {
        console.log('=== Get Physical Data Debug ===');
        console.log(`1. Fetching physical data for user ${userId}`);
        const response = await fetch(
            `${BASE_URL}/${userId}`,
            getAuthOptions()
        );
        
        if (!response.ok) {
            if (response.status === 404) {
                console.log('2. No physical data found, returning defaults');
                const defaults: PhysicalData = {
                    height: 0,
                    weight: 0,
                    units: {
                        height: 'cm' as const,
                        weight: 'kg' as const,
                        measurements: 'cm' as const
                    },
                    preferences: {
                        showMetric: true
                    }
                };
                console.log('3. Default values:', defaults);
                return defaults;
            }
            throw new Error('Failed to load physical data');
        }

        const data = await response.json();
        console.log('2. Raw API response:', data);
        
        // Create base response with required fields
        const physicalData: PhysicalData = {
            height: data.height || 0,
            weight: data.weight || 0,
            units: {
                height: data.units?.height || 'cm',
                weight: data.units?.weight || 'kg',
                measurements: data.units?.measurements || 'cm'
            },
            preferences: {
                showMetric: data.preferences?.showMetric ?? true
            }
        };

        // Add optional measurements only if they exist and are not null
        if (typeof data.chest === 'number') physicalData.chest = data.chest;
        if (typeof data.waist === 'number') physicalData.waist = data.waist;
        if (typeof data.hips === 'number') physicalData.hips = data.hips;

        console.log('3. Processed physical data:', physicalData);
        return physicalData;
    },

    async updatePhysicalData(userId: number, data: PhysicalData): Promise<PhysicalData> {
        console.log('=== Physical Data Update Debug ===');
        console.log('1. Raw input data:', data);
        
        // Validate required data
        if (!data || !data.units || !data.preferences) {
            console.error('Missing required data structures:', { data });
            throw new Error('Invalid physical data structure');
        }

        // Create a clean payload that matches PhysicalData interface
        const payload: PhysicalData = {
            height: data.height,
            weight: data.weight,
            units: {
                height: data.units.height,
                weight: data.units.weight,
                measurements: data.units.measurements || 'cm'
            },
            preferences: {
                showMetric: data.preferences.showMetric
            }
        };

        console.log('2. Final payload:', payload);

        try {
            const response = await fetch(
                `${BASE_URL}/${userId}`,
                getAuthOptions('POST', payload)
            );

            if (!response.ok) {
                const error = await response.json();
                console.error('Update failed:', error);
                throw new Error(error.message || 'Failed to update physical data');
            }

            const result = await response.json();
            console.log('Update successful:', result);
            return result;
        } catch (err) {
            console.error('Update failed:', err);
            // Ensure we throw an Error object with a message
            if (err instanceof Error) {
                throw err;
            }
            throw new Error('Failed to update physical data');
        }
    },

    async getPhysicalHistory(
        userId: number,
        offset: number = 0,
        limit: number = 10
    ): Promise<PhysicalHistoryResponse> {
        console.log('=== Physical History Debug ===');
        console.log(`Fetching physical history for user ${userId}`);
        
        try {
            const response = await fetch(
                `${BASE_URL}/${userId}/history?offset=${offset}&limit=${limit}`,
                getAuthOptions()
            );

            if (!response.ok) {
                if (response.status === 404) {
                    console.log('No history found, returning empty array');
                    return {
                        items: [],
                        total: 0,
                        limit,
                        offset
                    };
                }
                const error = await response.json();
                console.error('History fetch failed:', error);
                throw new Error(error.message || 'Failed to load physical history');
            }

            const result = await response.json();
            console.log('History fetch successful:', result);
            return result;
        } catch (err) {
            console.error('History fetch failed:', err);
            // Ensure we throw an Error object with a message
            if (err instanceof Error) {
                throw err;
            }
            throw new Error('Failed to load physical history');
        }
    }
}; 