import { ConstraintManager } from '../../../services/ConstraintManager';
import { Exercise } from '../../../types/workout-types';
import { InjuryConstraint } from '../../../services/PromptBuilder';

describe('ConstraintManager', () => {
    let constraintManager: ConstraintManager;
    let mockExercise: Exercise;

    const defaultInjuryConstraints: Record<string, InjuryConstraint> = {
        'knee': {
            injury: 'knee',
            excludedExercises: ['squats', 'lunges'],
            excludedMuscleGroups: ['quadriceps'],
            maxIntensity: 'medium'
        }
    };

    beforeEach(() => {
        constraintManager = new ConstraintManager(defaultInjuryConstraints);
        mockExercise = {
            id: 'ex1',
            name: 'squats',
            type: 'strength',
            equipment: ['barbell'],
            targetMuscles: ['quadriceps', 'glutes'],
            difficulty: 'intermediate',
            instructions: 'Perform squats with proper form'
        };
    });

    describe('validateExerciseForConstraints', () => {
        test('identifies exercises excluded by injury constraints', () => {
            const result = constraintManager.validateExerciseForConstraints(
                mockExercise,
                ['knee'],
                ['barbell'],
                'intermediate'
            );

            expect(result.isValid).toBe(false);
            expect(result.reasons).toContain('Exercise excluded due to knee injury');
        });

        test('validates exercise when no constraints are violated', () => {
            const safeExercise: Exercise = {
                ...mockExercise,
                name: 'bench-press',
                type: 'strength',
                targetMuscles: ['chest', 'triceps']
            };

            const result = constraintManager.validateExerciseForConstraints(
                safeExercise,
                ['knee'],
                ['barbell'],
                'intermediate'
            );

            expect(result.isValid).toBe(true);
            expect(result.reasons).toHaveLength(0);
        });

        test('checks equipment availability', () => {
            const result = constraintManager.validateExerciseForConstraints(
                mockExercise,
                [],
                ['dumbbells'],
                'intermediate'
            );

            expect(result.isValid).toBe(false);
            expect(result.reasons).toContain('Required equipment not available');
        });

        test('validates exercise difficulty against experience level', () => {
            const result = constraintManager.validateExerciseForConstraints(
                mockExercise,
                [],
                ['barbell'],
                'beginner'
            );

            expect(result.isValid).toBe(false);
            expect(result.reasons).toContain('Exercise too difficult for experience level');
        });
    });

    describe('validateExerciseForInjuries', () => {
        test('identifies exercises targeting excluded muscle groups', () => {
            const result = constraintManager.validateExerciseForInjuries(
                mockExercise,
                ['knee']
            );

            expect(result.isValid).toBe(false);
            expect(result.reasons).toContain('Exercise targets excluded muscle groups: quadriceps');
        });

        test('validates exercise with no injury conflicts', () => {
            const safeExercise: Exercise = {
                ...mockExercise,
                name: 'pull-ups',
                type: 'strength',
                targetMuscles: ['back', 'biceps']
            };

            const result = constraintManager.validateExerciseForInjuries(
                safeExercise,
                ['knee']
            );

            expect(result.isValid).toBe(true);
            expect(result.reasons).toHaveLength(0);
        });
    });

    describe('suggestAlternativeExercises', () => {
        const alternatives: Exercise[] = [
            {
                id: 'ex2',
                name: 'leg-press',
                type: 'strength',
                equipment: ['machine'],
                targetMuscles: ['quadriceps', 'glutes'],
                difficulty: 'beginner',
                instructions: 'Use leg press machine'
            },
            {
                id: 'ex3',
                name: 'bodyweight-squats',
                type: 'strength',
                equipment: [],
                targetMuscles: ['quadriceps', 'glutes'],
                difficulty: 'beginner',
                instructions: 'Perform bodyweight squats'
            }
        ];

        test('suggests safer alternatives for excluded exercises', () => {
            const suggestions = constraintManager.suggestAlternativeExercises(
                mockExercise,
                ['knee'],
                ['machine', 'bodyweight'],
                'beginner',
                alternatives
            );

            expect(suggestions).toHaveLength(1);
            expect(suggestions[0].name).toBe('leg-press');
            expect(suggestions[0].difficulty).toBe('beginner');
        });

        test('returns empty array when no suitable alternatives found', () => {
            const suggestions = constraintManager.suggestAlternativeExercises(
                mockExercise,
                ['knee'],
                [],
                'beginner',
                alternatives
            );

            expect(suggestions).toHaveLength(0);
        });
    });

    describe('calculateExerciseSimilarity', () => {
        test('calculates similarity based on target muscles', () => {
            const similarExercise: Exercise = {
                ...mockExercise,
                name: 'front-squats',
                type: 'strength',
                targetMuscles: ['quadriceps', 'glutes']
            };

            const differentExercise: Exercise = {
                ...mockExercise,
                name: 'bench-press',
                type: 'strength',
                targetMuscles: ['chest', 'triceps']
            };

            const similarityScore1 = constraintManager.calculateExerciseSimilarity(
                mockExercise,
                similarExercise
            );
            const similarityScore2 = constraintManager.calculateExerciseSimilarity(
                mockExercise,
                differentExercise
            );

            expect(similarityScore1).toBeGreaterThan(similarityScore2);
        });

        test('considers exercise type in similarity calculation', () => {
            const sameTypeExercise: Exercise = {
                ...mockExercise,
                name: 'deadlifts',
                type: 'strength'
            };

            const differentTypeExercise: Exercise = {
                ...mockExercise,
                name: 'bicep-curls',
                type: 'cardio'
            };

            const similarityScore1 = constraintManager.calculateExerciseSimilarity(
                mockExercise,
                sameTypeExercise
            );
            const similarityScore2 = constraintManager.calculateExerciseSimilarity(
                mockExercise,
                differentTypeExercise
            );

            expect(similarityScore1).toBeGreaterThan(similarityScore2);
        });
    });
}); 