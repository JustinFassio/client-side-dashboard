import { Equipment, EquipmentSet, WorkoutZone } from '../types/equipment-types';
import { ApiResponse, ApiError } from '../../../dashboard/types/api';

class EquipmentService {
    private readonly baseUrl: string;
    private static instance: EquipmentService | null = null;
    private initialized: boolean = false;
    private abortController: AbortController | null = null;

    private constructor() {
        this.baseUrl = `${window.athleteDashboardData?.apiUrl || '/wp-json/athlete-dashboard/v1'}/equipment`;
    }

    public static getInstance(): EquipmentService {
        if (!EquipmentService.instance) {
            EquipmentService.instance = new EquipmentService();
        }
        return EquipmentService.instance;
    }

    private initialize(): void {
        if (this.initialized) {
            return;
        }
        if (this.abortController) {
            this.abortController.abort();
        }
        this.abortController = new AbortController();
        this.initialized = true;
    }

    public cleanup(): void {
        if (this.abortController) {
            this.abortController.abort();
            this.abortController = null;
        }
        this.initialized = false;
        EquipmentService.instance = null;
    }

    private async fetchWithSignal(url: string, options: RequestInit = {}): Promise<Response> {
        this.initialize();
        const signal = this.abortController?.signal;
        
        try {
            const response = await fetch(url, {
                ...options,
                headers: {
                    ...options.headers,
                    'X-WP-Nonce': window.athleteDashboardData?.nonce || ''
                },
                signal
            });
            return response;
        } catch (error) {
            if (error instanceof DOMException && error.name === 'AbortError') {
                throw error;
            }
            throw error;
        }
    }

    async getEquipment(): Promise<ApiResponse<Equipment[]>> {
        try {
            const response = await this.fetchWithSignal(`${this.baseUrl}/items`);

            if (!response.ok) {
                const error: ApiError = {
                    code: 'equipment_fetch_error',
                    message: `HTTP error! status: ${response.status}`,
                    status: response.status
                };
                return { data: null, error };
            }

            const data = await response.json();
            return { data, error: null };
        } catch (error) {
            if (error instanceof DOMException && error.name === 'AbortError') {
                return { data: null, error: null };
            }
            const apiError: ApiError = {
                code: 'equipment_fetch_error',
                message: error instanceof Error ? error.message : 'Failed to fetch equipment',
                status: 500
            };
            return { data: null, error: apiError };
        }
    }

    async addEquipment(equipment: Omit<Equipment, 'id'>): Promise<ApiResponse<Equipment>> {
        this.initialize();
        try {
            const response = await fetch(`${this.baseUrl}/items`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.athleteDashboardData?.nonce || ''
                },
                body: JSON.stringify(equipment)
            });

            if (!response.ok) {
                const error: ApiError = {
                    code: 'equipment_add_error',
                    message: `HTTP error! status: ${response.status}`,
                    status: response.status
                };
                return { data: null, error };
            }

            const data = await response.json();
            return { data, error: null };
        } catch (error) {
            const apiError: ApiError = {
                code: 'equipment_add_error',
                message: error instanceof Error ? error.message : 'Failed to add equipment',
                status: 500
            };
            return { data: null, error: apiError };
        }
    }

    async updateEquipment(id: string, updates: Partial<Equipment>): Promise<ApiResponse<Equipment>> {
        this.initialize();
        try {
            const response = await fetch(`${this.baseUrl}/items/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.athleteDashboardData?.nonce || ''
                },
                body: JSON.stringify(updates)
            });

            if (!response.ok) {
                const error: ApiError = {
                    code: 'equipment_update_error',
                    message: `HTTP error! status: ${response.status}`,
                    status: response.status
                };
                return { data: null, error };
            }

            const data = await response.json();
            return { data, error: null };
        } catch (error) {
            const apiError: ApiError = {
                code: 'equipment_update_error',
                message: error instanceof Error ? error.message : 'Failed to update equipment',
                status: 500
            };
            return { data: null, error: apiError };
        }
    }

    async deleteEquipment(id: string): Promise<ApiResponse<void>> {
        this.initialize();
        try {
            const response = await fetch(`${this.baseUrl}/items/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-WP-Nonce': window.athleteDashboardData?.nonce || ''
                }
            });

            if (!response.ok) {
                const error: ApiError = {
                    code: 'equipment_delete_error',
                    message: `HTTP error! status: ${response.status}`,
                    status: response.status
                };
                return { data: null, error };
            }

            return { data: undefined, error: null };
        } catch (error) {
            const apiError: ApiError = {
                code: 'equipment_delete_error',
                message: error instanceof Error ? error.message : 'Failed to delete equipment',
                status: 500
            };
            return { data: null, error: apiError };
        }
    }

    async getEquipmentSets(): Promise<ApiResponse<EquipmentSet[]>> {
        this.initialize();
        try {
            const response = await fetch(`${this.baseUrl}/sets`, {
                headers: {
                    'X-WP-Nonce': window.athleteDashboardData?.nonce || ''
                }
            });

            if (!response.ok) {
                const error: ApiError = {
                    code: 'equipment_sets_fetch_error',
                    message: `HTTP error! status: ${response.status}`,
                    status: response.status
                };
                return { data: null, error };
            }

            const data = await response.json();
            return { data, error: null };
        } catch (error) {
            const apiError: ApiError = {
                code: 'equipment_sets_fetch_error',
                message: error instanceof Error ? error.message : 'Failed to fetch equipment sets',
                status: 500
            };
            return { data: null, error: apiError };
        }
    }

    async getWorkoutZones(): Promise<ApiResponse<WorkoutZone[]>> {
        this.initialize();
        try {
            const response = await fetch(`${this.baseUrl}/zones`, {
                headers: {
                    'X-WP-Nonce': window.athleteDashboardData?.nonce || ''
                }
            });

            if (!response.ok) {
                const error: ApiError = {
                    code: 'workout_zones_fetch_error',
                    message: `HTTP error! status: ${response.status}`,
                    status: response.status
                };
                return { data: null, error };
            }

            const data = await response.json();
            return { data, error: null };
        } catch (error) {
            const apiError: ApiError = {
                code: 'workout_zones_fetch_error',
                message: error instanceof Error ? error.message : 'Failed to fetch workout zones',
                status: 500
            };
            return { data: null, error: apiError };
        }
    }
}

export const equipmentService = EquipmentService.getInstance(); 