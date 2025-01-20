import { 
    WorkoutPlan, 
    Exercise, 
    ExerciseConstraints, 
    AIPrompt,
    WorkoutModification,
    HistoryFilters,
    WorkoutPreferences,
    UserProfile,
    TrainingPreferences,
    EquipmentSet,
    WorkoutError,
    WorkoutErrorCode
} from '../types/workout-types';
import { PromptBuilder } from './PromptBuilder';
import { ConstraintManager } from './ConstraintManager';
import axios from 'axios';

/**
 * Service responsible for generating and managing AI-powered workout plans.
 * Integrates with an external AI service while enforcing user constraints and preferences.
 */
export interface AIIntegrationService {
    generateWorkoutPlan(prompt: AIPrompt): Promise<WorkoutPlan>;
    validateExerciseSafety(exercise: Exercise, userProfile: any): Promise<boolean>;
    suggestAlternatives(exercise: Exercise, constraints: ExerciseConstraints): Promise<Exercise[]>;
    modifyWorkoutPlan(workout: WorkoutPlan, modifications: WorkoutModification): Promise<WorkoutPlan>;
    getWorkoutPlanById(id: string): Promise<WorkoutPlan>;
    getExerciseById(id: string): Promise<Exercise>;
    getWorkoutHistory(userId: number, filters?: HistoryFilters): Promise<WorkoutPlan[]>;
    saveWorkoutPlan(workout: WorkoutPlan): Promise<void>;
}

/**
 * Response structure from the AI service API
 */
interface APIResponse {
    workout?: {
        exercises: Exercise[];
        id: string;
        name: string;
        description: string;
        difficulty: string;
        duration: number;
        targetGoals: string[];
    };
    error?: {
        message: string;
        code: string;
    };
}

/**
 * Main service class for generating and managing AI-powered workouts.
 * Handles integration with AI service, constraint validation, and workout management.
 */
export class AIService implements AIIntegrationService {
    private readonly apiKey: string;
    private readonly axios: typeof axios;

    /**
     * Creates a new instance of AIService
     * @param promptBuilder - Service for building AI prompts based on user preferences
     * @param constraintManager - Service for validating exercises against user constraints
     * @param apiEndpoint - Base URL for the AI service API
     * @param apiKey - Authentication key for the AI service
     * @param axiosInstance - Optional custom axios instance for making HTTP requests
     */
    constructor(
        private readonly promptBuilder: PromptBuilder,
        private readonly constraintManager: ConstraintManager,
        private readonly apiEndpoint: string = '/api/workout-generator',
        apiKey: string = '',
        axiosInstance: typeof axios = axios
    ) {
        this.apiKey = apiKey;
        this.axios = axiosInstance;
    }

    /**
     * Makes an HTTP request to the AI service
     * @param url - Endpoint URL
     * @param method - HTTP method
     * @param data - Optional request payload
     * @throws {WorkoutError} If the request fails or returns invalid data
     */
    private async makeRequest<T extends APIResponse>(url: string, method: string, data?: any): Promise<T> {
        try {
            const response = await axios<T>({
                method,
                url,
                data,
                timeout: 5000,
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.apiKey}`
                }
            });
            return response.data;
        } catch (error: unknown) {
            // Check if error is an AxiosError by checking its properties
            if (typeof error === 'object' && error !== null && 'isAxiosError' in error) {
                const axiosError = error as any;

                // Handle timeout errors
                if (axiosError.code === 'ECONNABORTED') {
                    throw new WorkoutError(
                        'timeout of 5000ms exceeded',
                        WorkoutErrorCode.GENERATION_FAILED
                    );
                }
                
                // Handle network connection errors
                if (axiosError.code === 'ECONNREFUSED' || !axiosError.response) {
                    throw new WorkoutError(
                        'Failed to connect to API endpoint',
                        WorkoutErrorCode.GENERATION_FAILED
                    );
                }
                
                // Handle rate limit errors
                if (axiosError.response?.status === 429) {
                    throw new WorkoutError(
                        'Rate limit exceeded',
                        WorkoutErrorCode.RATE_LIMIT_EXCEEDED
                    );
                }

                // Handle other API errors with response
                if (axiosError.response?.data?.message) {
                    throw new WorkoutError(
                        axiosError.response.data.message,
                        WorkoutErrorCode.GENERATION_FAILED
                    );
                }

                // Handle API errors without specific message
                throw new WorkoutError(
                    'API request failed',
                    WorkoutErrorCode.GENERATION_FAILED
                );
            }

            // For any other type of error
            throw new WorkoutError(
                error instanceof Error ? error.message : 'Unknown error occurred',
                WorkoutErrorCode.GENERATION_FAILED
            );
        }
    }

    /**
     * Validates a list of exercises against user profile and equipment constraints
     * @param exercises - List of exercises to validate
     * @param profile - User profile containing injuries and experience level
     * @param equipment - Available equipment for the workout
     * @returns Array of valid exercises that meet all constraints
     */
    private async validateExercises(
        exercises: Exercise[],
        profile: UserProfile,
        equipment: EquipmentSet
    ): Promise<Exercise[]> {
        return exercises.filter(exercise => 
            this.constraintManager.validateExerciseForConstraints(
                exercise,
                {
                    injuries: profile.injuries || [],
                    equipment: equipment.available || [],
                    experienceLevel: profile.experienceLevel
                }
            )
        );
    }

    /**
     * Generates a workout plan based on user profile, preferences, and available equipment
     * @param profile - User profile containing experience level and injuries
     * @param preferences - User's workout preferences
     * @param equipment - Available equipment for the workout
     * @throws {WorkoutError} If workout generation fails or no valid exercises are found
     * @returns Generated workout plan with validated exercises
     */
    public async generateWorkout(
        profile: UserProfile,
        preferences: WorkoutPreferences,
        equipment: EquipmentSet
    ): Promise<WorkoutPlan> {
        try {
            const prompt = this.promptBuilder.buildWorkoutPrompt(profile, preferences, equipment);
            const response = await this.makeRequest('/api/workout/generate', 'POST', prompt);

            if (!response.workout?.exercises || response.workout.exercises.length === 0) {
                throw new WorkoutError('No valid exercises found in AI response', WorkoutErrorCode.GENERATION_FAILED);
            }

            const validatedExercises = await this.validateExercises(
                response.workout.exercises,
                profile,
                equipment
            );

            if (validatedExercises.length === 0) {
                throw new WorkoutError('No valid exercises found after validation', WorkoutErrorCode.VALIDATION_FAILED);
            }

            return {
                id: crypto.randomUUID(),
                name: `Workout for ${profile.id}`,
                description: 'AI Generated Workout Plan',
                exercises: validatedExercises,
                duration: response.workout.duration,
                difficulty: response.workout.difficulty,
                targetGoals: response.workout.targetGoals,
                equipment: equipment.available,
                createdAt: new Date().toISOString(),
                updatedAt: new Date().toISOString()
            };
        } catch (error) {
            // If it's already a WorkoutError, rethrow it
            if (error instanceof WorkoutError) {
                throw error;
            }
            // For Axios errors
            if (axios.isAxiosError(error) && error.response) {
                if (error.response.status === 429) {
                    throw new WorkoutError('Rate limit exceeded', WorkoutErrorCode.RATE_LIMIT_EXCEEDED);
                }
                throw new WorkoutError(
                    error.response.data?.message || 'API request failed',
                    WorkoutErrorCode.GENERATION_FAILED
                );
            }
            // For any other type of error
            throw new WorkoutError(
                error instanceof Error ? error.message : 'Failed to generate workout plan',
                WorkoutErrorCode.GENERATION_FAILED
            );
        }
    }

    /**
     * Handles error conversion to WorkoutError type
     * @param error - Original error
     * @returns Standardized WorkoutError
     */
    private handleError(error: unknown): WorkoutError {
        const message = error instanceof Error ? error.message : 'An unknown error occurred';
        return new WorkoutError(message, WorkoutErrorCode.GENERATION_FAILED);
    }

    /**
     * Validates if an exercise is safe for a user based on their profile
     * @param exercise - Exercise to validate
     * @param userProfile - User profile containing injuries and limitations
     * @returns Boolean indicating if the exercise is safe
     */
    async validateExerciseSafety(exercise: Exercise, userProfile: any): Promise<boolean> {
        const response = await fetch(`${this.apiEndpoint}/validate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ exercise, userProfile })
        });

        if (!response.ok) {
            throw new Error('Failed to validate exercise safety');
        }

        const { isValid } = await response.json();
        return isValid;
    }

    /**
     * Suggests alternative exercises based on given constraints
     * @param exercise - Original exercise to find alternatives for
     * @param constraints - Constraints to apply when finding alternatives
     * @returns Array of alternative exercises that meet the constraints
     */
    async suggestAlternatives(exercise: Exercise, constraints: ExerciseConstraints): Promise<Exercise[]> {
        const response = await fetch(`${this.apiEndpoint}/alternatives`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ exercise, constraints })
        });

        if (!response.ok) {
            throw new Error('Failed to suggest alternatives');
        }

        return response.json();
    }

    /**
     * Modifies an existing workout plan
     * @param workout - Original workout plan
     * @param modifications - Requested modifications
     * @returns Modified workout plan
     */
    async modifyWorkoutPlan(workout: WorkoutPlan, modifications: WorkoutModification): Promise<WorkoutPlan> {
        const response = await fetch(`${this.apiEndpoint}/modify`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ workout, modifications })
        });

        if (!response.ok) {
            throw new Error('Failed to modify workout plan');
        }

        return response.json();
    }

    /**
     * Retrieves a workout plan by its ID
     * @param id - Workout plan ID
     * @returns Workout plan
     */
    async getWorkoutPlanById(id: string): Promise<WorkoutPlan> {
        const response = await fetch(`${this.apiEndpoint}/workout/${id}`, {
            headers: {
                'X-API-Key': this.apiKey
            }
        });

        if (!response.ok) {
            throw new Error('Failed to fetch workout plan');
        }

        return response.json();
    }

    /**
     * Retrieves an exercise by its ID
     * @param id - Exercise ID
     * @returns Exercise details
     */
    async getExerciseById(id: string): Promise<Exercise> {
        const response = await fetch(`${this.apiEndpoint}/exercise/${id}`, {
            headers: {
                'X-API-Key': this.apiKey
            }
        });

        if (!response.ok) {
            throw new Error('Failed to fetch exercise');
        }

        return response.json();
    }

    /**
     * Retrieves workout history for a user
     * @param userId - User ID
     * @param filters - Optional filters to apply
     * @returns Array of workout plans
     */
    async getWorkoutHistory(userId: number, filters?: HistoryFilters): Promise<WorkoutPlan[]> {
        const queryParams = new URLSearchParams();
        if (filters) {
            Object.entries(filters).forEach(([key, value]) => {
                if (value !== undefined) {
                    if (Array.isArray(value)) {
                        value.forEach(v => queryParams.append(key, v.toString()));
                    } else {
                        queryParams.append(key, value.toString());
                    }
                }
            });
        }

        const response = await fetch(`${this.apiEndpoint}/history/${userId}?${queryParams}`, {
            headers: {
                'X-API-Key': this.apiKey
            }
        });

        if (!response.ok) {
            throw new Error('Failed to fetch workout history');
        }

        return response.json();
    }

    /**
     * Saves a workout plan
     * @param workout - Workout plan to save
     */
    async saveWorkoutPlan(workout: WorkoutPlan): Promise<void> {
        const response = await fetch(`${this.apiEndpoint}/save`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(workout)
        });

        if (!response.ok) {
            throw new Error('Failed to save workout plan');
        }
    }

    /**
     * Generates a workout plan from an AI prompt
     * @param prompt - AI prompt containing workout requirements
     * @throws {WorkoutError} If generation fails or response is invalid
     * @returns Generated workout plan
     */
    async generateWorkoutPlan(prompt: AIPrompt): Promise<WorkoutPlan> {
        try {
            const response = await this.makeRequest<APIResponse>('/api/workout/generate', 'POST', prompt);
            
            if (response.error) {
                throw new WorkoutError(response.error.message, WorkoutErrorCode.GENERATION_FAILED);
            }

            if (!response.workout) {
                throw new WorkoutError('Invalid API response format', WorkoutErrorCode.GENERATION_FAILED);
            }

            const { workout } = response;
            return {
                id: workout.id,
                name: workout.name,
                description: workout.description,
                difficulty: workout.difficulty,
                duration: workout.duration,
                exercises: workout.exercises,
                targetGoals: workout.targetGoals
            };
        } catch (error) {
            if (error instanceof WorkoutError) {
                throw error;
            }
            throw new WorkoutError(
                error instanceof Error ? error.message : 'Failed to generate workout plan',
                WorkoutErrorCode.GENERATION_FAILED
            );
        }
    }
} 