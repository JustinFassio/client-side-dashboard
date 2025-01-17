import { PhysicalData, PhysicalHistory } from '../types/physical';

const BASE_URL = '/wp-json/athlete-dashboard/v1/profile/physical';

interface PhysicalHistoryResponse {
    items: PhysicalHistory[];
    total: number;
    limit: number;
    offset: number;
}

// Get the WordPress nonce from the page
const getNonce = (): string => {
    const dashboardData = (window as any).athleteDashboardData;
    if (!dashboardData?.nonce) {
        console.error('WordPress nonce not found in athleteDashboardData');
        return '';
    }
    return dashboardData.nonce;
};

// Common fetch options for authenticated requests
const getAuthOptions = (method: string = 'GET', body?: any): RequestInit => ({
    method,
    credentials: 'same-origin',
    headers: {
        'X-WP-Nonce': getNonce(),
        'Content-Type': 'application/json',
    },
    ...(body ? { body: JSON.stringify(body) } : {}),
});

export const physicalApi = {
    async getPhysicalData(userId: number): Promise<PhysicalData> {
        console.log(`Fetching physical data for user ${userId}`);
        const response = await fetch(
            `${BASE_URL}/${userId}`,
            getAuthOptions()
        );
        
        if (!response.ok) {
            if (response.status === 404) {
                console.log('No physical data found for new user, returning defaults');
                return {
                    height: 0,
                    weight: 0,
                    units: 'metric',
                    preferences: {
                        trackHistory: false,
                        showMetricAndImperial: false
                    }
                };
            }
            throw new Error('Failed to load physical data');
        }

        return response.json();
    },

    async updatePhysicalData(userId: number, data: PhysicalData): Promise<PhysicalData> {
        console.log(`Updating physical data for user ${userId}`, data);
        const response = await fetch(
            `${BASE_URL}/${userId}`,
            getAuthOptions('POST', data)
        );

        if (!response.ok) {
            throw new Error('Failed to update physical data');
        }

        return response.json();
    },

    async getPhysicalHistory(
        userId: number,
        offset: number = 0,
        limit: number = 10
    ): Promise<PhysicalHistoryResponse> {
        console.log(`Fetching physical history for user ${userId}`);
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
            throw new Error('Failed to load physical history');
        }

        return response.json();
    }
}; 