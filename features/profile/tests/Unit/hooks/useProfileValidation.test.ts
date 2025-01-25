import { renderHook } from '@testing-library/react-hooks';
import { useProfileValidation } from '../../../hooks/useProfileValidation';
import { ProfileData } from '../../../types/profile';

describe('useProfileValidation', () => {
    const validProfile: Partial<ProfileData> = {
        heightCm: 175,
        weightKg: 70,
        experienceLevel: 'intermediate',
        equipment: ['dumbbells', 'resistance_bands'],
        fitnessGoals: ['strength', 'endurance']
    };

    it('validates a valid profile successfully', () => {
        const { result } = renderHook(() => useProfileValidation());
        const validation = result.current.validateProfile(validProfile);
        
        expect(validation.isValid).toBe(true);
        expect(validation.fieldErrors).toBeUndefined();
        expect(validation.generalErrors).toBeUndefined();
    });

    describe('height validation', () => {
        it('validates height within range', () => {
            const { result } = renderHook(() => useProfileValidation());
            const validation = result.current.validateProfile({
                ...validProfile,
                heightCm: 180
            });
            
            expect(validation.isValid).toBe(true);
        });

        it('rejects height below minimum', () => {
            const { result } = renderHook(() => useProfileValidation());
            const validation = result.current.validateProfile({
                ...validProfile,
                heightCm: 99
            });
            
            expect(validation.isValid).toBe(false);
            expect(validation.fieldErrors?.heightCm).toContain('Height must be between 100cm and 250cm');
        });

        it('rejects height above maximum', () => {
            const { result } = renderHook(() => useProfileValidation());
            const validation = result.current.validateProfile({
                ...validProfile,
                heightCm: 251
            });
            
            expect(validation.isValid).toBe(false);
            expect(validation.fieldErrors?.heightCm).toContain('Height must be between 100cm and 250cm');
        });
    });

    describe('weight validation', () => {
        it('validates weight within range', () => {
            const { result } = renderHook(() => useProfileValidation());
            const validation = result.current.validateProfile({
                ...validProfile,
                weightKg: 75
            });
            
            expect(validation.isValid).toBe(true);
        });

        it('rejects weight below minimum', () => {
            const { result } = renderHook(() => useProfileValidation());
            const validation = result.current.validateProfile({
                ...validProfile,
                weightKg: 29
            });
            
            expect(validation.isValid).toBe(false);
            expect(validation.fieldErrors?.weightKg).toContain('Weight must be between 30kg and 200kg');
        });

        it('rejects weight above maximum', () => {
            const { result } = renderHook(() => useProfileValidation());
            const validation = result.current.validateProfile({
                ...validProfile,
                weightKg: 201
            });
            
            expect(validation.isValid).toBe(false);
            expect(validation.fieldErrors?.weightKg).toContain('Weight must be between 30kg and 200kg');
        });
    });

    describe('experience level validation', () => {
        it('validates valid experience levels', () => {
            const { result } = renderHook(() => useProfileValidation());
            const levels = ['beginner', 'intermediate', 'advanced'];
            
            levels.forEach(level => {
                const validation = result.current.validateProfile({
                    ...validProfile,
                    experienceLevel: level as ProfileData['experienceLevel']
                });
                expect(validation.isValid).toBe(true);
            });
        });

        it('rejects invalid experience level', () => {
            const { result } = renderHook(() => useProfileValidation());
            const validation = result.current.validateProfile({
                ...validProfile,
                experienceLevel: 'expert' as any
            });
            
            expect(validation.isValid).toBe(false);
            expect(validation.fieldErrors?.experienceLevel).toContain('Invalid experience level');
        });
    });

    describe('equipment validation', () => {
        it('validates valid equipment list', () => {
            const { result } = renderHook(() => useProfileValidation());
            const validation = result.current.validateProfile({
                ...validProfile,
                equipment: ['dumbbells', 'barbell', 'resistance_bands']
            });
            
            expect(validation.isValid).toBe(true);
        });

        it('rejects invalid equipment', () => {
            const { result } = renderHook(() => useProfileValidation());
            const validation = result.current.validateProfile({
                ...validProfile,
                equipment: ['dumbbells', 'invalid_equipment', 'resistance_bands']
            });
            
            expect(validation.isValid).toBe(false);
            expect(validation.fieldErrors?.equipment).toContain('Invalid equipment type at position 2');
        });
    });

    describe('fitness goals validation', () => {
        it('validates valid fitness goals', () => {
            const { result } = renderHook(() => useProfileValidation());
            const validation = result.current.validateProfile({
                ...validProfile,
                fitnessGoals: ['strength', 'endurance', 'flexibility']
            });
            
            expect(validation.isValid).toBe(true);
        });

        it('rejects invalid fitness goals', () => {
            const { result } = renderHook(() => useProfileValidation());
            const validation = result.current.validateProfile({
                ...validProfile,
                fitnessGoals: ['strength', 'invalid_goal', 'flexibility']
            });
            
            expect(validation.isValid).toBe(false);
            expect(validation.fieldErrors?.fitnessGoals).toContain('Invalid fitness goal at position 2');
        });
    });
}); 