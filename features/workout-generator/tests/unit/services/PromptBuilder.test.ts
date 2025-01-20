import { PromptBuilder, InjuryConstraint } from '../../../services/PromptBuilder';
import { WorkoutPreferences, UserProfile, TrainingPreferences, EquipmentSet } from '../../../types/workout-types';

describe('PromptBuilder', () => {
    let promptBuilder: PromptBuilder;
    let mockProfile: UserProfile;
    let mockPreferences: WorkoutPreferences;
    let mockTrainingPrefs: TrainingPreferences;
    let mockEquipment: EquipmentSet;

    const mockInjuryConstraints: Record<string, InjuryConstraint> = {
        'knee': {
            injury: 'knee',
            excludedExercises: ['squats', 'lunges'],
            excludedMuscleGroups: ['quadriceps'],
            maxIntensity: 'medium'
        }
    };

    beforeEach(() => {
        promptBuilder = new PromptBuilder(mockInjuryConstraints);

        mockProfile = {
            id: '123',
            injuries: ['knee'],
            heightCm: 180,
            weightKg: 75,
            experienceLevel: 'intermediate'
        };

        mockPreferences = {
            fitnessLevel: 'intermediate',
            availableEquipment: ['dumbbells', 'barbell'],
            preferredDuration: 45,
            targetMuscleGroups: ['chest', 'quadriceps', 'back'],
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
    });

    test('builds prompt with injury constraints', async () => {
        const prompt = await promptBuilder.buildWorkoutPrompt(
            mockProfile,
            mockPreferences,
            mockTrainingPrefs,
            mockEquipment
        );

        expect(prompt.constraints.injuries).toHaveLength(1);
        expect(prompt.constraints.injuries[0]).toEqual(mockInjuryConstraints['knee']);
    });

    test('adjusts preferences based on injury constraints', async () => {
        const prompt = await promptBuilder.buildWorkoutPrompt(
            mockProfile,
            mockPreferences,
            mockTrainingPrefs,
            mockEquipment
        );

        // Quadriceps should be removed from target muscle groups due to knee injury
        expect(prompt.preferences.targetMuscleGroups).not.toContain('quadriceps');
        expect(prompt.preferences.targetMuscleGroups).toContain('chest');
        expect(prompt.preferences.targetMuscleGroups).toContain('back');
    });

    test('respects time constraints', async () => {
        const longPreferences = {
            ...mockPreferences,
            preferredDuration: 90 // Longer than default max of 60
        };

        const prompt = await promptBuilder.buildWorkoutPrompt(
            mockProfile,
            longPreferences,
            mockTrainingPrefs,
            mockEquipment
        );

        expect(prompt.preferences.preferredDuration).toBe(60);
    });

    test('includes equipment constraints', async () => {
        const prompt = await promptBuilder.buildWorkoutPrompt(
            mockProfile,
            mockPreferences,
            mockTrainingPrefs,
            mockEquipment
        );

        expect(prompt.equipment).toEqual(mockEquipment.available);
    });

    test('handles profile with no injuries', async () => {
        const noInjuryProfile = {
            ...mockProfile,
            injuries: []
        };

        const prompt = await promptBuilder.buildWorkoutPrompt(
            noInjuryProfile,
            mockPreferences,
            mockTrainingPrefs,
            mockEquipment
        );

        expect(prompt.constraints.injuries).toHaveLength(0);
        expect(prompt.preferences.targetMuscleGroups).toEqual(mockPreferences.targetMuscleGroups);
    });

    test('handles custom injury constraints', async () => {
        const customConstraints = {
            'wrist': {
                injury: 'wrist',
                excludedExercises: ['push-ups', 'planks'],
                excludedMuscleGroups: ['forearms'],
                maxIntensity: 'low' as const
            }
        };

        const customPromptBuilder = new PromptBuilder(customConstraints);
        const customProfile = {
            ...mockProfile,
            injuries: ['wrist']
        };

        const prompt = await customPromptBuilder.buildWorkoutPrompt(
            customProfile,
            mockPreferences,
            mockTrainingPrefs,
            mockEquipment
        );

        expect(prompt.constraints.injuries).toHaveLength(1);
        expect(prompt.constraints.injuries[0]).toEqual(customConstraints['wrist']);
    });
}); 