import { renderHook, act } from '@testing-library/react-hooks';
import { useProfileValidation } from '../../hooks/useProfileValidation';

describe('useProfileValidation', () => {
    it('validates height within range', () => {
        const { result } = renderHook(() => useProfileValidation());

        act(() => {
            const isValid = result.current.validateHeight(180);
            expect(isValid).toBe(true);
        });

        act(() => {
            const isValid = result.current.validateHeight(400);
            expect(isValid).toBe(false);
        });
    });

    it('validates weight within range', () => {
        const { result } = renderHook(() => useProfileValidation());

        act(() => {
            const isValid = result.current.validateWeight(75);
            expect(isValid).toBe(true);
        });

        act(() => {
            const isValid = result.current.validateWeight(300);
            expect(isValid).toBe(false);
        });
    });

    it('validates BMI calculation', () => {
        const { result } = renderHook(() => useProfileValidation());

        act(() => {
            // Normal BMI case (height: 180cm, weight: 75kg)
            const isValid = result.current.validateBMI(180, 75);
            expect(isValid).toBe(true);
        });

        act(() => {
            // Underweight BMI case
            const isValid = result.current.validateBMI(180, 40);
            expect(isValid).toBe(false);
        });

        act(() => {
            // Overweight BMI case
            const isValid = result.current.validateBMI(180, 150);
            expect(isValid).toBe(false);
        });
    });

    it('validates complete form data', () => {
        const { result } = renderHook(() => useProfileValidation());

        const validData = {
            heightCm: 180,
            weightKg: 75,
            units: {
                height: 'cm',
                weight: 'kg'
            }
        };

        const invalidData = {
            heightCm: 400,
            weightKg: 300,
            units: {
                height: 'cm',
                weight: 'kg'
            }
        };

        act(() => {
            const { isValid, errors } = result.current.validateForm(validData);
            expect(isValid).toBe(true);
            expect(errors).toEqual({});
        });

        act(() => {
            const { isValid, errors } = result.current.validateForm(invalidData);
            expect(isValid).toBe(false);
            expect(errors).toHaveProperty('heightCm');
            expect(errors).toHaveProperty('weightKg');
        });
    });

    it('validates unit conversion', () => {
        const { result } = renderHook(() => useProfileValidation());

        act(() => {
            const { height, weight } = result.current.convertToMetric({
                heightFeet: 6,
                heightInches: 0,
                weight: 165,
                units: {
                    height: 'ft',
                    weight: 'lbs'
                }
            });

            expect(height).toBeCloseTo(183, 0); // 6ft ≈ 183cm
            expect(weight).toBeCloseTo(75, 0);  // 165lbs ≈ 75kg
        });
    });

    it('handles missing or invalid input', () => {
        const { result } = renderHook(() => useProfileValidation());

        act(() => {
            const { isValid, errors } = result.current.validateForm({});
            expect(isValid).toBe(false);
            expect(errors).toHaveProperty('heightCm');
            expect(errors).toHaveProperty('weightKg');
        });

        act(() => {
            const { isValid, errors } = result.current.validateForm({
                heightCm: 'invalid',
                weightKg: 'invalid'
            });
            expect(isValid).toBe(false);
            expect(errors).toHaveProperty('heightCm');
            expect(errors).toHaveProperty('weightKg');
        });
    });
}); 