import { Equipment, EquipmentSet, WorkoutZone } from '../../types/equipment-types';
import { ApiResponse, ApiError } from '../../../../dashboard/types/api';

interface EquipmentRecommendation {
    type: 'purchase' | 'optimization' | 'maintenance';
    priority: 'high' | 'medium' | 'low';
    description: string;
    reason: string;
    suggestedActions: string[];
}

interface EquipmentUsagePattern {
    equipmentId: string;
    frequency: number; // times used per week
    lastUsed: string; // ISO date string
    usageDuration: number; // minutes
}

interface EquipmentAnalysis {
    recommendations: EquipmentRecommendation[];
    usagePatterns: EquipmentUsagePattern[];
    gapAnalysis: {
        missingEquipmentTypes: string[];
        underutilizedEquipment: string[];
        potentialUpgrades: string[];
    };
}

class EquipmentAIService {
    private readonly baseUrl: string;
    private static instance: EquipmentAIService | null = null;

    private constructor() {
        this.baseUrl = `${window.athleteDashboardData?.apiUrl || '/wp-json/athlete-dashboard/v1'}/equipment/ai`;
    }

    public static getInstance(): EquipmentAIService {
        if (!EquipmentAIService.instance) {
            EquipmentAIService.instance = new EquipmentAIService();
        }
        return EquipmentAIService.instance;
    }

    async getEquipmentRecommendations(
        currentEquipment: Equipment[],
        userGoals: string[],
        fitnessLevel: string
    ): Promise<ApiResponse<EquipmentRecommendation[]>> {
        try {
            const response = await fetch(`${this.baseUrl}/recommendations`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.athleteDashboardData?.nonce || ''
                },
                body: JSON.stringify({
                    equipment: currentEquipment,
                    goals: userGoals,
                    fitnessLevel
                })
            });

            if (!response.ok) {
                const error: ApiError = {
                    code: 'ai_recommendations_error',
                    message: `HTTP error! status: ${response.status}`,
                    status: response.status
                };
                return { data: null, error };
            }

            const data = await response.json();
            return { data, error: null };
        } catch (error) {
            const apiError: ApiError = {
                code: 'ai_recommendations_error',
                message: error instanceof Error ? error.message : 'Failed to get AI recommendations',
                status: 500
            };
            return { data: null, error: apiError };
        }
    }

    async analyzeEquipmentUsage(
        equipment: Equipment[],
        usageHistory: EquipmentUsagePattern[]
    ): Promise<ApiResponse<EquipmentAnalysis>> {
        try {
            const response = await fetch(`${this.baseUrl}/analyze`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.athleteDashboardData?.nonce || ''
                },
                body: JSON.stringify({
                    equipment,
                    usageHistory
                })
            });

            if (!response.ok) {
                const error: ApiError = {
                    code: 'ai_analysis_error',
                    message: `HTTP error! status: ${response.status}`,
                    status: response.status
                };
                return { data: null, error };
            }

            const data = await response.json();
            return { data, error: null };
        } catch (error) {
            const apiError: ApiError = {
                code: 'ai_analysis_error',
                message: error instanceof Error ? error.message : 'Failed to analyze equipment usage',
                status: 500
            };
            return { data: null, error: apiError };
        }
    }

    async optimizeEquipmentLayout(
        equipment: Equipment[],
        spaceConstraints: {
            width: number;
            length: number;
            height?: number;
        }
    ): Promise<ApiResponse<{
        layout: {
            equipmentId: string;
            position: { x: number; y: number; z?: number };
            rotation: number;
        }[];
        suggestions: string[];
    }>> {
        try {
            const response = await fetch(`${this.baseUrl}/optimize-layout`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.athleteDashboardData?.nonce || ''
                },
                body: JSON.stringify({
                    equipment,
                    spaceConstraints
                })
            });

            if (!response.ok) {
                const error: ApiError = {
                    code: 'ai_layout_optimization_error',
                    message: `HTTP error! status: ${response.status}`,
                    status: response.status
                };
                return { data: null, error };
            }

            const data = await response.json();
            return { data, error: null };
        } catch (error) {
            const apiError: ApiError = {
                code: 'ai_layout_optimization_error',
                message: error instanceof Error ? error.message : 'Failed to optimize equipment layout',
                status: 500
            };
            return { data: null, error: apiError };
        }
    }

    async suggestMaintenanceSchedule(
        equipment: Equipment[],
        usagePatterns: EquipmentUsagePattern[]
    ): Promise<ApiResponse<{
        equipmentId: string;
        maintenanceTasks: {
            task: string;
            frequency: string;
            nextDue: string;
            priority: 'high' | 'medium' | 'low';
        }[];
    }[]>> {
        try {
            const response = await fetch(`${this.baseUrl}/maintenance`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.athleteDashboardData?.nonce || ''
                },
                body: JSON.stringify({
                    equipment,
                    usagePatterns
                })
            });

            if (!response.ok) {
                const error: ApiError = {
                    code: 'ai_maintenance_error',
                    message: `HTTP error! status: ${response.status}`,
                    status: response.status
                };
                return { data: null, error };
            }

            const data = await response.json();
            return { data, error: null };
        } catch (error) {
            const apiError: ApiError = {
                code: 'ai_maintenance_error',
                message: error instanceof Error ? error.message : 'Failed to get maintenance suggestions',
                status: 500
            };
            return { data: null, error: apiError };
        }
    }
}

export const equipmentAIService = EquipmentAIService.getInstance(); 