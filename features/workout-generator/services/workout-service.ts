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

import { ProfileService } from '../../profile/services/ProfileService';
import { ProfileData, Injury } from '../../profile/types/profile';
import { AIIntegrationService } from './AIService';
import { AnalyticsService } from '.';
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
        private profileService: ProfileService,
        private aiService: AIIntegrationService,
        private analyticsService: AnalyticsService,
        private authService: AuthService
    ) {
        this.cache = new WorkoutCache();
        this.validator = new WorkoutValidator();
        this.securityValidator = new SecurityValidator();
    }

    private convertToUserProfile(profile: ProfileData): UserProfile {
        return {
            id: profile.id.toString(),
            injuries: profile.injuries.map((injury: Injury) => injury.name),
            heightCm: 170, // Default value since not in profile
            weightKg: 70,  // Default value since not in profile
            experienceLevel: 'beginner' // Default value since not in profile
        };
    }

    async generateWorkout(userId: number, preferences: WorkoutPreferences): Promise<WorkoutPlan> {
        const startTime = performance.now();
        
        try {
            // Validate access
            await this.authService.validateAccess(userId);
            
            // Validate input
            this.securityValidator.validateInput(preferences);
            
            // Gather user data
            const profile = await this.profileService.fetchProfile(userId);
            const userProfile = this.convertToUserProfile(profile);
            
            // Generate AI prompt
            const prompt: AIPrompt = {
                profile: userProfile,
                preferences,
                trainingPreferences: {
                    preferredDays: [], // Default empty since not in profile
                    preferredTime: 'morning', // Default value
                    focusAreas: [] // Default empty since not in profile
                },
                equipment: [], // Default empty since not in profile
                constraints: {
                    injuries: userProfile.injuries,
                    equipment: [],
                    experienceLevel: userProfile.experienceLevel,
                    timeConstraints: {
                        maxDuration: preferences.preferredDuration * 60, // convert to minutes to seconds
                        minRestPeriod: preferences.minRestPeriod || 60
                    }
                }
            };
            
            // Generate workout plan
            const workoutPlan = await this.aiService.generateWorkoutPlan(prompt);
            
            // Validate safety
            const validationResult = await this.validator.validate(workoutPlan, {
                maxExercises: preferences.maxExercises ?? 10,
                minRestPeriod: preferences.minRestPeriod ?? 60,
                requiredWarmup: true
            });
            
            if (!validationResult.isValid) {
                throw new WorkoutServiceError(
                    'Generated workout failed safety validation',
                    WorkoutErrorCode.SAFETY_VALIDATION_FAILED,
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
                    WorkoutErrorCode.GENERATION_FAILED,
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
                maxExercises: currentWorkout.preferences?.maxExercises ?? 10,
                minRestPeriod: currentWorkout.preferences?.minRestPeriod ?? 60,
                requiredWarmup: true
            });
            
            if (!validationResult.isValid) {
                throw new WorkoutServiceError(
                    'Workout failed safety validation',
                    WorkoutErrorCode.SAFETY_VALIDATION_FAILED,
                    validationResult.errors
                );
            }
            
            return modifiedWorkout;
            
        } catch (error) {
            throw new WorkoutServiceError(
                'Failed to modify workout',
                WorkoutErrorCode.MODIFICATION_FAILED,
                error
            );
        }
    }

    async saveWorkout(workout: WorkoutPlan): Promise<void> {
        try {
            // Validate workout before saving
            const validationResult = await this.validator.validate(workout, {
                maxExercises: workout.preferences?.maxExercises ?? 10,
                minRestPeriod: workout.preferences?.minRestPeriod ?? 60,
                requiredWarmup: true
            });
            
            if (!validationResult.isValid) {
                throw new WorkoutServiceError(
                    'Workout failed safety validation',
                    WorkoutErrorCode.SAFETY_VALIDATION_FAILED,
                    validationResult.errors
                );
            }
            
            // Save to database/storage
            await this.aiService.saveWorkoutPlan(workout);
            
            // Invalidate cache
            this.cache.invalidate(`history:${workout.userId}`);
            
        } catch (error) {
            throw new WorkoutServiceError(
                'Failed to save workout',
                WorkoutErrorCode.SAVE_FAILED,
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
                    throw new WorkoutServiceError(
                        'Failed to fetch workout history',
                        WorkoutErrorCode.HISTORY_FETCH_FAILED,
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
                    WorkoutErrorCode.NO_ALTERNATIVES,
                    { exerciseId, constraints }
                );
            }
            
            // Return first alternative (assumed to be best match)
            return alternatives[0];
            
        } catch (error) {
            throw new WorkoutServiceError(
                'Failed to find exercise alternative',
                WorkoutErrorCode.ALTERNATIVE_FAILED,
                error
            );
        }
    }

    async validateWorkoutSafety(workout: WorkoutPlan, userProfile: UserProfile): Promise<ValidationResult> {
        try {
            return await this.validator.validate(workout, {
                maxExercises: workout.preferences?.maxExercises ?? 10,
                minRestPeriod: workout.preferences?.minRestPeriod ?? 60,
                requiredWarmup: true
            });
        } catch (error) {
            throw new WorkoutServiceError(
                'Validation failed',
                WorkoutErrorCode.VALIDATION_FAILED,
                error
            );
        }
    }

    private async getWorkoutById(workoutId: string): Promise<WorkoutPlan> {
        try {
            return await this.aiService.getWorkoutPlanById(workoutId);
        } catch (error) {
            throw new WorkoutServiceError(
                'Workout not found',
                WorkoutErrorCode.WORKOUT_NOT_FOUND,
                { workoutId }
            );
        }
    }
} 