import { 
    WorkoutPlan, 
    Exercise, 
    ExerciseConstraints, 
    AIPrompt,
    WorkoutModification,
    HistoryFilters
} from '../types/workout-types';

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

export class AIService implements AIIntegrationService {
    private readonly API_ENDPOINT = '/wp-json/athlete-dashboard/v1/ai';

    constructor(private apiKey: string) {}

    async generateWorkoutPlan(prompt: AIPrompt): Promise<WorkoutPlan> {
        const response = await fetch(`${this.API_ENDPOINT}/generate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Key': this.apiKey
            },
            body: JSON.stringify(prompt)
        });

        if (!response.ok) {
            throw new Error('Failed to generate workout plan');
        }

        return response.json();
    }

    async validateExerciseSafety(exercise: Exercise, userProfile: any): Promise<boolean> {
        const response = await fetch(`${this.API_ENDPOINT}/validate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Key': this.apiKey
            },
            body: JSON.stringify({ exercise, userProfile })
        });

        if (!response.ok) {
            throw new Error('Failed to validate exercise safety');
        }

        const { isValid } = await response.json();
        return isValid;
    }

    async suggestAlternatives(exercise: Exercise, constraints: ExerciseConstraints): Promise<Exercise[]> {
        const response = await fetch(`${this.API_ENDPOINT}/alternatives`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Key': this.apiKey
            },
            body: JSON.stringify({ exercise, constraints })
        });

        if (!response.ok) {
            throw new Error('Failed to suggest alternatives');
        }

        return response.json();
    }

    async modifyWorkoutPlan(workout: WorkoutPlan, modifications: WorkoutModification): Promise<WorkoutPlan> {
        const response = await fetch(`${this.API_ENDPOINT}/modify`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Key': this.apiKey
            },
            body: JSON.stringify({ workout, modifications })
        });

        if (!response.ok) {
            throw new Error('Failed to modify workout plan');
        }

        return response.json();
    }

    async getWorkoutPlanById(id: string): Promise<WorkoutPlan> {
        const response = await fetch(`${this.API_ENDPOINT}/workout/${id}`, {
            headers: {
                'X-API-Key': this.apiKey
            }
        });

        if (!response.ok) {
            throw new Error('Failed to fetch workout plan');
        }

        return response.json();
    }

    async getExerciseById(id: string): Promise<Exercise> {
        const response = await fetch(`${this.API_ENDPOINT}/exercise/${id}`, {
            headers: {
                'X-API-Key': this.apiKey
            }
        });

        if (!response.ok) {
            throw new Error('Failed to fetch exercise');
        }

        return response.json();
    }

    async getWorkoutHistory(userId: number, filters?: HistoryFilters): Promise<WorkoutPlan[]> {
        const queryParams = new URLSearchParams();
        if (filters) {
            Object.entries(filters).forEach(([key, value]) => {
                queryParams.append(key, value.toString());
            });
        }

        const response = await fetch(`${this.API_ENDPOINT}/history/${userId}?${queryParams}`, {
            headers: {
                'X-API-Key': this.apiKey
            }
        });

        if (!response.ok) {
            throw new Error('Failed to fetch workout history');
        }

        return response.json();
    }

    async saveWorkoutPlan(workout: WorkoutPlan): Promise<void> {
        const response = await fetch(`${this.API_ENDPOINT}/save`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Key': this.apiKey
            },
            body: JSON.stringify(workout)
        });

        if (!response.ok) {
            throw new Error('Failed to save workout plan');
        }
    }
} 