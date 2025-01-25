import { PromptBuilder } from '../../services/PromptBuilder';
import { ConstraintManager } from '../../services/ConstraintManager';
import { Exercise, UserProfile, WorkoutPreferences, TrainingPreferences, EquipmentSet } from '../../types/workout-types';

describe('PromptBuilder and ConstraintManager Integration', () => {
    let promptBuilder: PromptBuilder;
    let constraintManager: ConstraintManager;
    let mockProfile: UserProfile;
    let mockPreferences: WorkoutPreferences;
    let mockTrainingPrefs: TrainingPreferences;
    let mockEquipment: EquipmentSet;
    let mockExercises: Exercise[];

    beforeEach(() => {
        // Initialize with shared injury constraints
        const injuryConstraints = {
            'knee': {
                injury: 'knee',
                excludedExercises: ['squats', 'lunges'],
                excludedMuscleGroups: ['quadriceps'],
                maxIntensity: 'medium' as const
            },
            'shoulder': {
                injury: 'shoulder',
                excludedExercises: ['overhead-press', 'push-ups'],
                excludedMuscleGroups: ['shoulders'],
                maxIntensity: 'low' as const
            }
        };

        promptBuilder = new PromptBuilder(injuryConstraints);
        constraintManager = new ConstraintManager(injuryConstraints);

        mockProfile = {
            id: '123',
            injuries: ['knee', 'shoulder'],
            heightCm: 180,
            weightKg: 75,
            experienceLevel: 'intermediate'
        };

        mockPreferences = {
            fitnessLevel: 'intermediate',
            availableEquipment: ['dumbbells', 'barbell'],
            preferredDuration: 45,
            targetMuscleGroups: ['chest', 'quadriceps', 'shoulders', 'back'],
            healthConditions: [],
            workoutFrequency: 3
        };

        mockTrainingPrefs = {
            preferredDays: ['monday', 'wednesday', 'friday'],
            preferredTime: 'morning',
            focusAreas: ['strength', 'hypertrophy']
        };

        mockEquipment = {
            available: ['dumbbells', 'barbell', 'bench'],
            preferred: ['dumbbells']
        };

        mockExercises = [
            {
                id: 'ex1',
                name: 'squats',
                type: 'strength',
                equipment: ['barbell'],
                targetMuscles: ['quadriceps', 'glutes'],
                difficulty: 'intermediate',
                instructions: 'Perform squats with proper form'
            },
            {
                id: 'ex2',
                name: 'bench-press',
                type: 'strength',
                equipment: ['barbell', 'bench'],
                targetMuscles: ['chest', 'triceps'],
                difficulty: 'intermediate',
                instructions: 'Perform bench press'
            },
            {
                id: 'ex3',
                name: 'overhead-press',
                type: 'strength',
                equipment: ['dumbbells'],
                targetMuscles: ['shoulders', 'triceps'],
                difficulty: 'intermediate',
                instructions: 'Perform overhead press'
            }
        ];
    });

    test('prompt constraints match constraint manager validation', async () => {
        // Generate prompt with multiple injuries
        const prompt = await promptBuilder.buildWorkoutPrompt(
            mockProfile,
            mockPreferences,
            mockTrainingPrefs,
            mockEquipment
        );

        // Verify each exercise against both systems
        mockExercises.forEach(exercise => {
            const validationResult = constraintManager.validateExerciseForConstraints(
                exercise,
                mockProfile.injuries,
                mockEquipment.available,
                mockPreferences.fitnessLevel
            );

            // If exercise targets excluded muscle groups, it should be excluded from prompt
            const isExcludedInPrompt = prompt.preferences.targetMuscleGroups
                .every(muscle => !exercise.targetMuscles.includes(muscle));

            if (!validationResult.isValid) {
                expect(isExcludedInPrompt).toBe(true);
            }
        });
    });

    test('alternative exercises suggested by constraint manager fit prompt constraints', async () => {
        const prompt = await promptBuilder.buildWorkoutPrompt(
            mockProfile,
            mockPreferences,
            mockTrainingPrefs,
            mockEquipment
        );

        // For each exercise that doesn't meet constraints
        mockExercises.forEach(exercise => {
            const validationResult = constraintManager.validateExerciseForConstraints(
                exercise,
                mockProfile.injuries,
                mockEquipment.available,
                mockPreferences.fitnessLevel
            );

            if (!validationResult.isValid) {
                // Get alternative exercises
                const alternatives = constraintManager.suggestAlternativeExercises(
                    exercise,
                    mockProfile.injuries,
                    mockEquipment.available,
                    mockPreferences.fitnessLevel,
                    mockExercises
                );

                // Verify alternatives fit prompt constraints
                alternatives.forEach(alt => {
                    // Alternative should only target allowed muscle groups
                    const targetsAllowedMuscles = alt.targetMuscles
                        .some(muscle => prompt.preferences.targetMuscleGroups.includes(muscle));
                    expect(targetsAllowedMuscles).toBe(true);

                    // Alternative should use available equipment
                    const usesAvailableEquipment = alt.equipment
                        .every(eq => prompt.equipment.includes(eq));
                    expect(usesAvailableEquipment).toBe(true);
                });
            }
        });
    });

    test('prompt time constraints are respected by both systems', async () => {
        const longPreferences = {
            ...mockPreferences,
            preferredDuration: 90 // Longer than default max
        };

        const prompt = await promptBuilder.buildWorkoutPrompt(
            mockProfile,
            longPreferences,
            mockTrainingPrefs,
            mockEquipment
        );

        // Verify time constraints in prompt
        expect(prompt.constraints.timeConstraints.maxDuration).toBe(60);
        expect(prompt.preferences.preferredDuration).toBe(60);

        // Verify constraint manager respects the same limits
        mockExercises.forEach(exercise => {
            if (exercise.duration && exercise.duration > prompt.constraints.timeConstraints.maxDuration) {
                const validationResult = constraintManager.validateExerciseForConstraints(
                    exercise,
                    mockProfile.injuries,
                    mockEquipment.available,
                    mockPreferences.fitnessLevel
                );
                expect(validationResult.isValid).toBe(false);
            }
        });
    });

    test('handles conflicting constraints gracefully', async () => {
        // Create a scenario with conflicting constraints
        const conflictingProfile: UserProfile = {
            ...mockProfile,
            injuries: ['knee', 'shoulder'],
            experienceLevel: 'beginner'
        };

        const conflictingPreferences: WorkoutPreferences = {
            ...mockPreferences,
            fitnessLevel: 'advanced', // Conflicts with profile
            targetMuscleGroups: ['quadriceps', 'shoulders'] // Conflicts with injuries
        };

        const prompt = await promptBuilder.buildWorkoutPrompt(
            conflictingProfile,
            conflictingPreferences,
            mockTrainingPrefs,
            mockEquipment
        );

        // Verify prompt resolves conflicts
        expect(prompt.constraints.experienceLevel).toBe('beginner');
        expect(prompt.preferences.targetMuscleGroups).not.toContain('quadriceps');
        expect(prompt.preferences.targetMuscleGroups).not.toContain('shoulders');

        // Verify constraint manager agrees with resolutions
        mockExercises.forEach(exercise => {
            if (exercise.difficulty === 'advanced' || 
                exercise.targetMuscles.includes('quadriceps') ||
                exercise.targetMuscles.includes('shoulders')) {
                const validationResult = constraintManager.validateExerciseForConstraints(
                    exercise,
                    conflictingProfile.injuries,
                    mockEquipment.available,
                    conflictingProfile.experienceLevel
                );
                expect(validationResult.isValid).toBe(false);
            }
        });
    });
}); 