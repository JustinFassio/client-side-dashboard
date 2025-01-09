import { ApiResponse, ApiError } from '../../../dashboard/types/api';
import { WorkoutPlan, WorkoutPreferences, GeneratorSettings, WorkoutResponse } from '../types/workout-types';

class WorkoutGeneratorService {
    private readonly baseUrl: string;

    constructor() {
        this.baseUrl = window.athleteDashboardData?.apiUrl || '/wp-json/athlete-dashboard/v1';
    }

    async generateWorkout(
        userId: number,
        preferences: WorkoutPreferences,
        settings: GeneratorSettings
    ): Promise<ApiResponse<WorkoutPlan>> {
        try {
            const response = await fetch(`${this.baseUrl}/workout-generator/generate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.athleteDashboardData?.nonce || ''
                },
                body: JSON.stringify({ preferences, settings })
            });

            if (!response.ok) {
                const error: ApiError = {
                    code: 'workout_generation_error',
                    message: `HTTP error! status: ${response.status}`,
                    status: response.status
                };
                return { data: null, error };
            }

            const data = await response.json();
            return {
                data: data as WorkoutPlan,
                error: null
            };
        } catch (error) {
            const apiError: ApiError = {
                code: 'workout_generation_error',
                message: error instanceof Error ? error.message : 'Failed to generate workout',
                status: 500
            };
            return { data: null, error: apiError };
        }
    }

    async saveWorkout(userId: number, workout: WorkoutPlan): Promise<ApiResponse<WorkoutPlan>> {
        try {
            const response = await fetch(`${this.baseUrl}/workout-generator/save`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.athleteDashboardData?.nonce || ''
                },
                body: JSON.stringify({ userId, workout })
            });

            if (!response.ok) {
                const error: ApiError = {
                    code: 'workout_save_error',
                    message: `HTTP error! status: ${response.status}`,
                    status: response.status
                };
                return { data: null, error };
            }

            const data = await response.json();
            return {
                data: data as WorkoutPlan,
                error: null
            };
        } catch (error) {
            const apiError: ApiError = {
                code: 'workout_save_error',
                message: error instanceof Error ? error.message : 'Failed to save workout',
                status: 500
            };
            return { data: null, error: apiError };
        }
    }

    async getWorkoutHistory(userId: number): Promise<ApiResponse<WorkoutPlan[]>> {
        try {
            const response = await fetch(`${this.baseUrl}/workout-generator/history/${userId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.athleteDashboardData?.nonce || ''
                }
            });

            if (!response.ok) {
                const error: ApiError = {
                    code: 'workout_history_error',
                    message: `HTTP error! status: ${response.status}`,
                    status: response.status
                };
                return { data: null, error };
            }

            const data = await response.json();
            return {
                data: data as WorkoutPlan[],
                error: null
            };
        } catch (error) {
            const apiError: ApiError = {
                code: 'workout_history_error',
                message: error instanceof Error ? error.message : 'Failed to fetch workout history',
                status: 500
            };
            return { data: null, error: apiError };
        }
    }
}

export const workoutService = new WorkoutGeneratorService(); 