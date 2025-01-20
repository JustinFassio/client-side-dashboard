import { 
    WorkoutPlan, 
    WorkoutPreferences, 
    WorkoutModification,
    Exercise,
    ExerciseConstraints,
    WorkoutValidation,
    HistoryFilters,
    UserProfile
} from '../types/workout-types';

import { WorkoutService } from './workout-service';
import { WorkoutCache } from './WorkoutCache';
import { createHash } from 'crypto';

export class CachedWorkoutService implements WorkoutService {
    private cache: WorkoutCache;
    private baseService: WorkoutService;

    constructor(baseService: WorkoutService) {
        this.baseService = baseService;
        this.cache = new WorkoutCache({
            ttl: 5 * 60 * 1000, // 5 minutes
            maxSize: 100 // Store up to 100 workouts
        });
    }

    private generateCacheKey(userId: number, preferences: WorkoutPreferences): string {
        // Create a deterministic hash of the user ID and preferences
        const data = JSON.stringify({ userId, preferences });
        return createHash('md5').update(data).digest('hex');
    }

    async generateWorkout(userId: number, preferences: WorkoutPreferences): Promise<WorkoutPlan> {
        const cacheKey = this.generateCacheKey(userId, preferences);
        
        return this.cache.getOrFetch(cacheKey, async () => {
            const workout = await this.baseService.generateWorkout(userId, preferences);
            return workout;
        });
    }

    async modifyWorkout(workoutId: string, modifications: WorkoutModification): Promise<WorkoutPlan> {
        // Don't cache modifications as they're unique operations
        return this.baseService.modifyWorkout(workoutId, modifications);
    }

    async saveWorkout(workout: WorkoutPlan): Promise<void> {
        await this.baseService.saveWorkout(workout);
        
        // Since WorkoutPlan doesn't include userId or preferences, we'll just invalidate the history cache
        this.cache.invalidate(`history:${workout.id}`);
    }

    async getWorkoutHistory(userId: number, filters?: HistoryFilters): Promise<WorkoutPlan[]> {
        const cacheKey = `history:${userId}${filters ? ':' + JSON.stringify(filters) : ''}`;
        
        return this.cache.getOrFetch(cacheKey, async () => {
            return this.baseService.getWorkoutHistory(userId, filters);
        });
    }

    async provideExerciseAlternative(exerciseId: string, constraints: ExerciseConstraints): Promise<Exercise> {
        // Don't cache alternative suggestions as they should be fresh each time
        return this.baseService.provideExerciseAlternative(exerciseId, constraints);
    }

    async validateWorkoutSafety(workout: WorkoutPlan, userProfile: UserProfile): Promise<WorkoutValidation> {
        // Don't cache safety validations as they should be fresh each time
        return this.baseService.validateWorkoutSafety(workout, userProfile);
    }
} 