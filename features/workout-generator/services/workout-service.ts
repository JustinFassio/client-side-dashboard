import { 
    WorkoutPlan, 
    WorkoutPreferences, 
    WorkoutModification, 
    Exercise,
    ExerciseConstraints,
    ValidationResult,
    HistoryFilters,
    WorkoutErrorCode,
    AIPrompt,
    UserProfile,
    TrainingPreferences,
    EquipmentSet,
    WorkoutRequest
} from '../types/workout-types';

import { ProfileIntegrationService } from '../../profile/services/ProfileService';
import { AIIntegrationService } from './AIService';
import { AnalyticsService } from './AnalyticsService';
import { WorkoutCache } from './WorkoutCache';
import { SecurityValidator } from './SecurityValidator';
import { WorkoutValidator } from './WorkoutValidator';
import { AuthService } from '../../auth/services/AuthService';

export class WorkoutServiceError extends Error {
    constructor(
        message: string,
        public code: WorkoutErrorCode,
        public details?: any
    ) {
        super(message);
        this.name = 'WorkoutServiceError';
    }
}

export interface WorkoutService {
    // Core Generation
    generateWorkout(userId: number, preferences: WorkoutPreferences): Promise<WorkoutPlan>;
    modifyWorkout(workoutId: string, modifications: WorkoutModification): Promise<WorkoutPlan>;
    
    // History & Storage
    saveWorkout(workout: WorkoutPlan): Promise<void>;
    getWorkoutHistory(userId: number, filters?: HistoryFilters): Promise<WorkoutPlan[]>;
    
    // Real-time Interaction
    provideExerciseAlternative(exerciseId: string, constraints: ExerciseConstraints): Promise<Exercise>;
    validateWorkoutSafety(workout: WorkoutPlan, userProfile: UserProfile): Promise<ValidationResult>;
}

export class WorkoutGeneratorService implements WorkoutService {
    private cache: WorkoutCache;
    private validator: WorkoutValidator;
    private securityValidator: SecurityValidator;

    constructor(
        private profileService: ProfileIntegrationService,
        private aiService: AIIntegrationService,
        private analyticsService: AnalyticsService,
        private authService: AuthService
    ) {
        this.cache = new WorkoutCache();
        this.validator = new WorkoutValidator();
        this.securityValidator = new SecurityValidator();
    }

    async generateWorkout(userId: number, preferences: WorkoutPreferences): Promise<WorkoutPlan> {
        const startTime = performance.now();
        
        try {
            // Validate access
            await this.authService.validateAccess(userId);
            
            // Validate input
            this.securityValidator.validateInput(preferences);
            
            // Gather user data
            const [profile, trainingPrefs, equipment] = await Promise.all([
                this.profileService.getUserProfile(userId),
                this.profileService.getTrainingPreferences(userId),
                this.profileService.getEquipmentAvailability(userId)
            ]);
            
            // Generate AI prompt
            const prompt: AIPrompt = {
                profile,
                preferences,
                trainingPreferences: trainingPrefs,
                equipment
            };
            
            // Generate workout plan
            const workoutPlan = await this.aiService.generateWorkoutPlan(prompt);
            
            // Validate safety
            const validationResult = await this.validator.validate(workoutPlan, {
                maxExercises: preferences.maxExercises || 10,
                minRestPeriod: preferences.minRestPeriod || 60,
                requiredWarmup: true
            });
            
            if (!validationResult.isValid) {
                throw new WorkoutServiceError(
                    'Generated workout failed safety validation',
                    'SAFETY_VALIDATION_FAILED',
                    validationResult.errors
                );
            }
            
            // Record metrics
            this.analyticsService.recordSuccess('generateWorkout', performance.now() - startTime);
            
            return workoutPlan;
            
        } catch (error) {
            this.analyticsService.recordError('generateWorkout', error);
            throw error instanceof WorkoutServiceError 
                ? error 
                : new WorkoutServiceError(
                    'Failed to generate workout',
                    'GENERATION_FAILED',
                    error
                );
        }
    }

    async modifyWorkout(workoutId: string, modifications: WorkoutModification): Promise<WorkoutPlan> {
        try {
            // Validate modifications
            this.securityValidator.validateInput(modifications);
            
            // Get current workout
            const currentWorkout = await this.getWorkoutById(workoutId);
            
            // Apply modifications using AI service
            const modifiedWorkout = await this.aiService.modifyWorkoutPlan(currentWorkout, modifications);
            
            // Validate modified workout
            const validationResult = await this.validator.validate(modifiedWorkout, {
                maxExercises: currentWorkout.preferences.maxExercises,
                minRestPeriod: currentWorkout.preferences.minRestPeriod,
                requiredWarmup: true
            });
            
            if (!validationResult.isValid) {
                throw new WorkoutServiceError(
                    'Modified workout failed safety validation',
                    'SAFETY_VALIDATION_FAILED',
                    validationResult.errors
                );
            }
            
            return modifiedWorkout;
            
        } catch (error) {
            throw error instanceof WorkoutServiceError 
                ? error 
                : new WorkoutServiceError(
                    'Failed to modify workout',
                    'MODIFICATION_FAILED',
                    error
                );
        }
    }

    async saveWorkout(workout: WorkoutPlan): Promise<void> {
        try {
            // Validate workout before saving
            const validationResult = await this.validator.validate(workout, {
                maxExercises: workout.preferences.maxExercises,
                minRestPeriod: workout.preferences.minRestPeriod,
                requiredWarmup: true
            });
            
            if (!validationResult.isValid) {
                throw new WorkoutServiceError(
                    'Cannot save invalid workout',
                    'SAFETY_VALIDATION_FAILED',
                    validationResult.errors
                );
            }
            
            // Save to database/storage
            await this.aiService.saveWorkoutPlan(workout);
            
            // Invalidate cache
            this.cache.invalidate(`history:${workout.userId}`);
            
        } catch (error) {
            throw error instanceof WorkoutServiceError 
                ? error 
                : new WorkoutServiceError(
                    'Failed to save workout',
                    'SAVE_FAILED',
                    error
                );
        }
    }

    async getWorkoutHistory(userId: number, filters?: HistoryFilters): Promise<WorkoutPlan[]> {
        return this.cache.getOrFetch(
            `history:${userId}`,
            async () => {
                try {
                    await this.authService.validateAccess(userId);
                    return await this.aiService.getWorkoutHistory(userId, filters);
                } catch (error) {
                    throw error instanceof WorkoutServiceError 
                        ? error 
                        : new WorkoutServiceError(
                            'Failed to fetch workout history',
                            'HISTORY_FETCH_FAILED',
                            error
                        );
                }
            }
        );
    }

    async provideExerciseAlternative(exerciseId: string, constraints: ExerciseConstraints): Promise<Exercise> {
        try {
            // Get original exercise
            const exercise = await this.aiService.getExerciseById(exerciseId);
            
            // Generate alternatives
            const alternatives = await this.aiService.suggestAlternatives(exercise, constraints);
            
            if (!alternatives.length) {
                throw new WorkoutServiceError(
                    'No suitable alternatives found',
                    'NO_ALTERNATIVES',
                    { exerciseId, constraints }
                );
            }
            
            // Return first alternative (assumed to be best match)
            return alternatives[0];
            
        } catch (error) {
            throw error instanceof WorkoutServiceError 
                ? error 
                : new WorkoutServiceError(
                    'Failed to find exercise alternative',
                    'ALTERNATIVE_FAILED',
                    error
                );
        }
    }

    async validateWorkoutSafety(workout: WorkoutPlan, userProfile: UserProfile): Promise<ValidationResult> {
        try {
            return await this.validator.validate(workout, {
                maxExercises: workout.preferences.maxExercises,
                minRestPeriod: workout.preferences.minRestPeriod,
                requiredWarmup: true
            });
        } catch (error) {
            throw new WorkoutServiceError(
                'Failed to validate workout safety',
                'VALIDATION_FAILED',
                error
            );
        }
    }

    private async getWorkoutById(workoutId: string): Promise<WorkoutPlan> {
        try {
            return await this.aiService.getWorkoutPlanById(workoutId);
        } catch (error) {
            throw new WorkoutServiceError(
                'Failed to fetch workout',
                'WORKOUT_NOT_FOUND',
                error
            );
        }
    }
} 