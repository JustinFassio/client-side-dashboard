import { Exercise, ExerciseConstraints } from '../../../types/workout-types';
import { ConstraintManager } from '../../../services/ConstraintManager';

describe('ConstraintManager', () => {
    let constraintManager: ConstraintManager;
    let mockExercise: Exercise;
    let mockConstraints: ExerciseConstraints;

    beforeEach(() => {
        constraintManager = new ConstraintManager();
        mockExercise = {
            id: '1',
            name: 'Squat',
            type: 'strength',
            difficulty: 'intermediate',
            targetMuscles: ['quads', 'glutes', 'hamstrings'],
            equipment: ['dumbbells', 'barbell'],
            instructions: 'Perform exercise with proper form'
        };

        mockConstraints = {
            injuries: [],
            equipment: ['dumbbells', 'barbell'],
            experienceLevel: 'intermediate',
            maxIntensity: 'medium'
        };
    });

    describe('validateExerciseForConstraints', () => {
        it('validates exercise against constraints', () => {
            expect(constraintManager.validateExerciseForConstraints(mockExercise, mockConstraints)).toBe(true);
        });

        it('validates exercise with injury constraints', () => {
            mockConstraints.injuries = ['knee'];
            expect(constraintManager.validateExerciseForConstraints(mockExercise, mockConstraints)).toBe(false);
        });

        it('validates exercise with equipment constraints', () => {
            mockConstraints.equipment = ['resistance_bands'];
            expect(constraintManager.validateExerciseForConstraints(mockExercise, mockConstraints)).toBe(false);
        });

        it('validates exercise with experience level constraints', () => {
            mockConstraints.experienceLevel = 'beginner';
            expect(constraintManager.validateExerciseForConstraints(mockExercise, mockConstraints)).toBe(false);
        });
    });

    describe('suggestAlternativeExercises', () => {
        const mockExercises: Exercise[] = [
            {
                id: '1',
                name: 'Squat',
                type: 'strength',
                difficulty: 'intermediate',
                targetMuscles: ['quads', 'glutes', 'hamstrings'],
                equipment: ['dumbbells', 'barbell'],
                instructions: 'Perform exercise with proper form'
            },
            {
                id: '2',
                name: 'Push-up',
                type: 'strength',
                equipment: ['bodyweight'],
                targetMuscles: ['chest', 'shoulders', 'triceps'],
                difficulty: 'beginner',
                instructions: 'Perform exercise with proper form'
            },
            {
                id: '3',
                name: 'Lunges',
                type: 'strength',
                difficulty: 'intermediate',
                targetMuscles: ['quads', 'glutes', 'hamstrings'],
                equipment: ['dumbbells'],
                instructions: 'Perform exercise with proper form'
            }
        ];

        it('suggests alternative exercises based on constraints', () => {
            mockConstraints.equipment = ['dumbbells'];
            const alternatives = constraintManager.suggestAlternativeExercises(mockExercise, mockConstraints, mockExercises);
            expect(alternatives).toHaveLength(1);
            expect(alternatives[0].name).toBe('Lunges');
        });

        it('returns empty array when no valid alternatives found', () => {
            mockConstraints.injuries = ['knee'];
            const alternatives = constraintManager.suggestAlternativeExercises(mockExercise, mockConstraints, mockExercises);
            expect(alternatives).toHaveLength(0);
        });
    });
}); 