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
            excludedExercises: ['squats', 'lunges', 'jump-rope'],
            excludedMuscleGroups: ['quadriceps', 'hamstrings', 'calves'],
            maxIntensity: 'medium'
        }
    };

    beforeEach(() => {
        promptBuilder = new PromptBuilder();

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
            targetMuscleGroups: ['chest', 'back'],
            healthConditions: [],
            workoutFrequency: 3
        };

        mockTrainingPrefs = {
            preferredDays: ['monday', 'wednesday', 'friday'],
            preferredTime: 'morning',
            focusAreas: ['strength', 'hypertrophy']
        };

        mockEquipment = {
            available: ['dumbbells', 'barbell'],
            preferred: ['dumbbells']
        };
    });

    test('builds prompt with injury constraints', async () => {
        const result = promptBuilder.buildWorkoutPrompt(
            mockProfile,
            mockPreferences,
            mockEquipment
        );

        expect(result.constraints.injuries).toHaveLength(1);
        expect(result.constraints.injuries[0]).toEqual(mockInjuryConstraints['knee']);
    });

    test('adjusts preferences based on injury constraints', async () => {
        const result = promptBuilder.buildWorkoutPrompt(
            mockProfile,
            mockPreferences,
            mockEquipment
        );

        // Quadriceps should be removed from target muscle groups due to knee injury
        expect(result.preferences.targetMuscleGroups).not.toContain('quadriceps');
        expect(result.preferences.targetMuscleGroups).toContain('chest');
        expect(result.preferences.targetMuscleGroups).toContain('back');
    });

    test('respects time constraints', async () => {
        const longPreferences = {
            ...mockPreferences,
            preferredDuration: 90 // Longer than default max of 60
        };

        const result = promptBuilder.buildWorkoutPrompt(
            mockProfile,
            longPreferences,
            mockEquipment
        );

        expect(result.preferences.preferredDuration).toBe(30); // Default duration when invalid
    });

    test('includes equipment constraints', async () => {
        const result = promptBuilder.buildWorkoutPrompt(
            mockProfile,
            mockPreferences,
            mockEquipment
        );

        expect(result.equipment).toEqual(['dumbbells', 'barbell']);
    });

    test('handles profile with no injuries', async () => {
        const noInjuryProfile = {
            ...mockProfile,
            injuries: []
        };

        const result = promptBuilder.buildWorkoutPrompt(
            noInjuryProfile,
            mockPreferences,
            mockEquipment
        );

        expect(result.constraints.injuries).toHaveLength(0);
        expect(result.preferences.targetMuscleGroups).toEqual(mockPreferences.targetMuscleGroups);
    });

    test('handles custom injury constraints', async () => {
        // Use wrist injury which is in the hardcoded map
        const customProfile = {
            ...mockProfile,
            injuries: ['wrist']
        };

        const result = promptBuilder.buildWorkoutPrompt(
            customProfile,
            mockPreferences,
            mockEquipment
        );

        expect(result.constraints.injuries).toHaveLength(1);
        const wristConstraint = result.constraints.injuries[0];
        expect(wristConstraint.injury).toBe('wrist');
        expect(wristConstraint.excludedExercises).toContain('push-ups');
        expect(wristConstraint.excludedExercises).toContain('planks');
        expect(wristConstraint.excludedMuscleGroups).toContain('forearms');
    });

    describe('buildWorkoutPrompt', () => {
        it('builds prompt with no injuries', () => {
            const result = promptBuilder.buildWorkoutPrompt(
                mockProfile,
                mockPreferences,
                mockEquipment
            );

            expect(result.profile).toEqual(mockProfile);
            expect(result.preferences.preferredDuration).toBe(45);
            expect(result.equipment).toEqual(['dumbbells', 'barbell']);
            expect(result.constraints.injuries).toHaveLength(0);
        });

        it('builds prompt with injuries', () => {
            mockProfile.injuries = ['knee'];
            const result = promptBuilder.buildWorkoutPrompt(
                mockProfile,
                mockPreferences,
                mockEquipment
            );

            expect(result.constraints.injuries).toHaveLength(1);
            expect(result.constraints.injuries[0].injury).toBe('knee');
            expect(result.constraints.injuries[0].excludedExercises).toContain('squats');
        });

        it('builds prompt with equipment constraints', () => {
            mockEquipment.available = ['dumbbells'];
            const result = promptBuilder.buildWorkoutPrompt(
                mockProfile,
                mockPreferences,
                mockEquipment
            );

            expect(result.equipment).toEqual(['dumbbells']);
            expect(result.constraints.equipment).toEqual(['dumbbells']);
        });

        it('builds prompt with default duration', () => {
            mockPreferences.preferredDuration = 0;
            const result = promptBuilder.buildWorkoutPrompt(
                mockProfile,
                mockPreferences,
                mockEquipment
            );

            expect(result.preferences.preferredDuration).toBe(30);
        });
    });
}); 