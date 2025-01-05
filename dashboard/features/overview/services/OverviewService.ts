import { BaseFeatureService } from '../../../core/services/BaseFeatureService';
import { FeatureContext } from '../../../contracts/Feature';

export interface OverviewData {
    stats: {
        workouts_completed: number;
        active_programs: number;
        nutrition_score: number;
    };
    recent_activity: Array<{
        id: number;
        type: string;
        title: string;
        date: string;
    }>;
    goals: Array<{
        id: number;
        title: string;
        progress: number;
        target_date: string;
    }>;
}

export class OverviewService extends BaseFeatureService {
    constructor(context: FeatureContext) {
        super(context, 'overview');
    }

    async getOverviewData(userId: number): Promise<OverviewData> {
        return this.fetchWithCache<OverviewData>(`overview/${userId}`);
    }

    async updateGoal(goalId: number, progress: number): Promise<void> {
        await this.fetch(`overview/goals/${goalId}`, {
            method: 'PUT',
            body: { progress }
        });
        
        // Invalidate cache after update
        this.invalidateCache(`overview/${this.context.userId}`);
    }

    async dismissActivity(activityId: number): Promise<void> {
        await this.fetch(`overview/activity/${activityId}`, {
            method: 'DELETE'
        });
        
        // Invalidate cache after deletion
        this.invalidateCache(`overview/${this.context.userId}`);
    }
} 