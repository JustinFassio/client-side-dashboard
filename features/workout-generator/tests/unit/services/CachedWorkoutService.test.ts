import { 
    WorkoutPlan, 
    WorkoutPreferences,
    Exercise,
    ExerciseConstraints,
    WorkoutValidation,
    UserProfile,
    WorkoutModification
} from '../../../types/workout-types';
import { WorkoutService } from '../../../services/workout-service';
import { CachedWorkoutService } from '../../../services/CachedWorkoutService';
import { WorkoutCache } from '../../../services/WorkoutCache';

jest.mock('../../../services/WorkoutCache');

describe('CachedWorkoutService', () => {
    let baseService: jest.Mocked<WorkoutService>;
    let cachedService: CachedWorkoutService;
    let mockWorkout: WorkoutPlan;
    let mockPreferences: WorkoutPreferences;
    let mockProfile: UserProfile;
    let mockCache: jest.Mocked<WorkoutCache>;
    let mockCacheData: Map<string, any>;

    beforeEach(() => {
        jest.clearAllMocks();

        mockCacheData = new Map();

        mockCache = {
            getOrFetch: jest.fn().mockImplementation(async (key: string, fetchFn: () => Promise<any>) => {
                if (mockCacheData.has(key)) {
                    return mockCacheData.get(key);
                }
                const value = await fetchFn();
                mockCacheData.set(key, value);
                return value;
            }),
            set: jest.fn().mockImplementation((key: string, data: any) => {
                mockCacheData.set(key, data);
            }),
            get: jest.fn().mockImplementation((key: string) => {
                return mockCacheData.get(key);
            }),
            invalidate: jest.fn().mockImplementation((key: string) => {
                mockCacheData.delete(key);
            }),
            clear: jest.fn().mockImplementation(() => {
                mockCacheData.clear();
            }),
            cache: new Map(),
            config: { ttl: 300000, maxSize: 100 },
            isExpired: jest.fn().mockReturnValue(false),
            evictOldest: jest.fn()
        } as unknown as jest.Mocked<WorkoutCache>;

        (WorkoutCache as jest.MockedClass<typeof WorkoutCache>).mockImplementation(() => mockCache);

        baseService = {
            generateWorkout: jest.fn().mockResolvedValue(mockWorkout),
            modifyWorkout: jest.fn().mockResolvedValue(mockWorkout),
            saveWorkout: jest.fn().mockResolvedValue(mockWorkout),
            getWorkoutHistory: jest.fn().mockResolvedValue([mockWorkout]),
            provideExerciseAlternative: jest.fn().mockResolvedValue(mockWorkout),
            validateWorkoutSafety: jest.fn().mockResolvedValue({ isValid: true, errors: {} })
        };

        mockWorkout = {
            id: '123',
            name: 'Test Workout',
            description: 'A test workout plan',
            difficulty: 'intermediate',
            duration: 30,
            exercises: [{
                id: '1',
                name: 'Push-ups',
                type: 'strength',
                equipment: ['bodyweight'],
                targetMuscles: ['chest', 'shoulders'],
                difficulty: 'beginner',
                instructions: 'Do push-ups'
            }],
            targetGoals: ['strength']
        };

        mockPreferences = {
            fitnessLevel: 'intermediate',
            availableEquipment: ['bodyweight'],
            preferredDuration: 30,
            workoutFrequency: 3,
            targetMuscleGroups: ['chest', 'shoulders'],
            healthConditions: []
        };

        mockProfile = {
            id: '123',
            injuries: [],
            heightCm: 175,
            weightKg: 70,
            experienceLevel: 'beginner'
        };

        cachedService = new CachedWorkoutService(baseService);
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    describe('generateWorkout', () => {
        it('should cache workout generation results', async () => {
            const result1 = await cachedService.generateWorkout(1, mockPreferences);
            const result2 = await cachedService.generateWorkout(1, mockPreferences);

            expect(baseService.generateWorkout).toHaveBeenCalledTimes(1);
            expect(result1).toEqual(result2);
        });

        it('should use different cache keys for different preferences', async () => {
            const preferences2 = { ...mockPreferences, preferredDuration: 45 };
            
            await cachedService.generateWorkout(1, mockPreferences);
            await cachedService.generateWorkout(1, preferences2);

            expect(baseService.generateWorkout).toHaveBeenCalledTimes(2);
        });
    });

    describe('saveWorkout', () => {
        it('should invalidate relevant caches when saving a workout', async () => {
            await cachedService.saveWorkout(mockWorkout);
            expect(mockCache.invalidate).toHaveBeenCalledWith(`history:${mockWorkout.id}`);
        });
    });

    describe('getWorkoutHistory', () => {
        it('should cache workout history results', async () => {
            const result1 = await cachedService.getWorkoutHistory(1);
            const result2 = await cachedService.getWorkoutHistory(1);

            expect(baseService.getWorkoutHistory).toHaveBeenCalledTimes(1);
            expect(result1).toEqual(result2);
        });
    });

    describe('uncached operations', () => {
        it('should not cache workout modifications', async () => {
            const modification: WorkoutModification = {
                exerciseId: '1',
                action: 'replace'
            };

            await cachedService.modifyWorkout('123', modification);
            await cachedService.modifyWorkout('123', modification);

            expect(baseService.modifyWorkout).toHaveBeenCalledTimes(2);
        });

        it('should not cache safety validations', async () => {
            await cachedService.validateWorkoutSafety(mockWorkout, mockProfile);
            await cachedService.validateWorkoutSafety(mockWorkout, mockProfile);

            expect(baseService.validateWorkoutSafety).toHaveBeenCalledTimes(2);
        });
    });
}); 