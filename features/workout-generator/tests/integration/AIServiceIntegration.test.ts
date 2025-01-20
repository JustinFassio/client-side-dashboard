import axios from 'axios';
import { AIService } from '../../services/AIService';
import { PromptBuilder } from '../../services/PromptBuilder';
import { ConstraintManager } from '../../services/ConstraintManager';
import { 
    Exercise, 
    UserProfile, 
    WorkoutPreferences, 
    TrainingPreferences, 
    EquipmentSet,
    WorkoutErrorCode
} from '../../types/workout-types';
import { WorkoutError } from '../../types/workout-types';

jest.mock('axios');

describe('AIService Integration', () => {
    let aiService: AIService;
    let promptBuilder: PromptBuilder;
    let constraintManager: ConstraintManager;
    let mockProfile: UserProfile;
    let mockPreferences: WorkoutPreferences;
    let mockEquipment: EquipmentSet;
    let mockAxios: jest.Mocked<typeof axios>;
    
    const mockExercise: Exercise = {
        id: '1',
        name: 'push-ups',
        type: 'strength',
        equipment: ['none'],
        targetMuscles: ['chest', 'triceps'],
        difficulty: 'beginner',
        instructions: 'Perform push-ups with proper form'
    };

    beforeEach(() => {
        jest.clearAllMocks();
        (axios as jest.MockedFunction<typeof axios>).mockClear();

        // Initialize mocks
        mockProfile = {
            id: '123',
            experienceLevel: 'beginner',
            injuries: [],
            heightCm: 175,
            weightKg: 70
        };

        mockPreferences = {
            fitnessLevel: 'beginner',
            preferredDuration: 30,
            workoutFrequency: 3,
            availableEquipment: ['none'],
            targetMuscleGroups: ['chest', 'back'],
            healthConditions: []
        };

        mockEquipment = {
            available: ['none'],
            preferred: ['none']
        };

        // Mock Axios
        mockAxios = {
            post: jest.fn(),
            get: jest.fn(),
            put: jest.fn(),
            delete: jest.fn(),
            patch: jest.fn(),
            request: jest.fn(),
            isAxiosError: true
        } as unknown as jest.Mocked<typeof axios>;

        // Initialize services
        promptBuilder = new PromptBuilder();
        constraintManager = new ConstraintManager();
        aiService = new AIService(promptBuilder, constraintManager, '/api/workout-generator', '', mockAxios);
    });

    test('generates valid workout plan with constraints', async () => {
        const mockResponse = {
            data: {
                workout: {
                    id: '123',
                    name: 'Test Workout',
                    description: 'A test workout',
                    difficulty: 'beginner',
                    duration: 30,
                    exercises: [mockExercise],
                    targetGoals: ['strength']
                }
            }
        };
        (axios as jest.MockedFunction<typeof axios>).mockResolvedValueOnce(mockResponse);
        
        const result = await aiService.generateWorkout(mockProfile, mockPreferences, mockEquipment);
        expect(result.exercises).toHaveLength(1);
    });

    test('handles invalid exercises from AI response', async () => {
        const mockResponse = {
            data: {
                workout: {
                    id: '123',
                    name: 'Test Workout',
                    description: 'A test workout',
                    difficulty: 'beginner',
                    duration: 30,
                    exercises: [],
                    targetGoals: ['strength']
                }
            }
        };
        (axios as jest.MockedFunction<typeof axios>).mockResolvedValueOnce(mockResponse);

        await expect(aiService.generateWorkout(mockProfile, mockPreferences, mockEquipment))
            .rejects.toThrow(new WorkoutError('No valid exercises found in AI response', WorkoutErrorCode.GENERATION_FAILED));
    });

    test('handles API errors gracefully', async () => {
        const mockError = {
            isAxiosError: true,
            response: {
                status: 500,
                data: { message: 'API request failed' }
            },
            config: {},
            name: 'AxiosError',
            message: 'API request failed',
            toJSON: () => ({})
        };
        (axios as jest.MockedFunction<typeof axios>).mockRejectedValueOnce(mockError);

        await expect(aiService.generateWorkout(mockProfile, mockPreferences, mockEquipment))
            .rejects.toThrow(new WorkoutError('API request failed', WorkoutErrorCode.GENERATION_FAILED));
    });

    test('handles rate limit errors', async () => {
        const mockError = {
            isAxiosError: true,
            response: {
                status: 429,
                data: { message: 'Rate limit exceeded' }
            },
            config: {},
            name: 'AxiosError',
            message: 'Rate limit exceeded',
            toJSON: () => ({})
        };
        (axios as jest.MockedFunction<typeof axios>).mockRejectedValueOnce(mockError);

        await expect(aiService.generateWorkout(mockProfile, mockPreferences, mockEquipment))
            .rejects.toThrow(new WorkoutError('Rate limit exceeded', WorkoutErrorCode.RATE_LIMIT_EXCEEDED));
    });

    test('validates exercises against constraints', async () => {
        const mockResponse = {
            data: {
                workout: {
                    id: '123',
                    name: 'Test Workout',
                    description: 'A test workout',
                    difficulty: 'advanced',
                    duration: 30,
                    exercises: [{ ...mockExercise, difficulty: 'advanced' }],
                    targetGoals: ['strength']
                }
            }
        };
        (axios as jest.MockedFunction<typeof axios>).mockResolvedValueOnce(mockResponse);

        await expect(aiService.generateWorkout(mockProfile, mockPreferences, mockEquipment))
            .rejects.toThrow(new WorkoutError('No valid exercises found after validation', WorkoutErrorCode.VALIDATION_FAILED));
    });

    test('handles API timeout gracefully', async () => {
        const mockError = {
            isAxiosError: true,
            code: 'ECONNABORTED',
            message: 'timeout of 5000ms exceeded',
            config: {},
            name: 'AxiosError',
            toJSON: () => ({})
        };
        (axios as jest.MockedFunction<typeof axios>).mockRejectedValueOnce(mockError);

        await expect(aiService.generateWorkout(mockProfile, mockPreferences, mockEquipment))
            .rejects.toThrow(new WorkoutError('timeout of 5000ms exceeded', WorkoutErrorCode.GENERATION_FAILED));
    });

    test('handles network failure scenarios', async () => {
        const mockError = {
            isAxiosError: true,
            code: 'ECONNREFUSED',
            message: 'Failed to connect to API endpoint',
            config: {},
            name: 'AxiosError',
            toJSON: () => ({})
        };
        (axios as jest.MockedFunction<typeof axios>).mockRejectedValueOnce(mockError);

        await expect(aiService.generateWorkout(mockProfile, mockPreferences, mockEquipment))
            .rejects.toThrow(new WorkoutError('Failed to connect to API endpoint', WorkoutErrorCode.GENERATION_FAILED));
    });
}); 