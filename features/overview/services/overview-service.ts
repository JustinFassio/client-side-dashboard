import { ApiResponse, ApiError } from '../../../dashboard/types/api';

interface OverviewStats {
    workouts_completed: number;
    active_programs: number;
    nutrition_score: number;
}

interface Goal {
    id: string;
    title: string;
    description: string;
    target_date: string;
    progress: number;
    status: 'active' | 'completed' | 'cancelled';
}

interface Activity {
    id: string;
    type: string;
    description: string;
    timestamp: string;
    metadata?: Record<string, any>;
}

export interface OverviewData {
    stats: OverviewStats;
    recent_activity: Activity[];
    goals: Goal[];
}

class OverviewService {
    private readonly baseUrl: string;

    constructor() {
        this.baseUrl = window.athleteDashboardData?.apiUrl || '/wp-json/athlete-dashboard/v1';
    }

    async getOverview(userId: number): Promise<ApiResponse<OverviewData>> {
        try {
            const response = await fetch(`${this.baseUrl}/overview/${userId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.athleteDashboardData?.nonce || ''
                }
            });

            if (!response.ok) {
                const error: ApiError = {
                    code: 'overview_error',
                    message: `HTTP error! status: ${response.status}`,
                    status: response.status
                };
                return {
                    data: null,
                    error
                };
            }

            const data = await response.json();
            return {
                data: data as OverviewData,
                error: null
            };
        } catch (error) {
            const apiError: ApiError = {
                code: 'overview_error',
                message: error instanceof Error ? error.message : 'Failed to fetch overview data',
                status: 500
            };
            return {
                data: null,
                error: apiError
            };
        }
    }

    async getStats(userId: number): Promise<ApiResponse<OverviewStats>> {
        try {
            const response = await fetch(`${this.baseUrl}/overview/${userId}/stats`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.athleteDashboardData?.nonce || ''
                }
            });

            if (!response.ok) {
                const error: ApiError = {
                    code: 'stats_error',
                    message: `HTTP error! status: ${response.status}`,
                    status: response.status
                };
                return {
                    data: null,
                    error
                };
            }

            const data = await response.json();
            return {
                data: data as OverviewStats,
                error: null
            };
        } catch (error) {
            const apiError: ApiError = {
                code: 'stats_error',
                message: error instanceof Error ? error.message : 'Failed to fetch stats',
                status: 500
            };
            return {
                data: null,
                error: apiError
            };
        }
    }
}

export const overviewService = new OverviewService(); 