import { UnitConversion } from '../../../utils/unitConversion';

describe('UnitConversion', () => {
    describe('feetAndInchesToCm', () => {
        it('converts feet and inches to centimeters correctly', () => {
            expect(UnitConversion.feetAndInchesToCm(5, 10)).toBe(178); // 5'10" = ~177.8cm
            expect(UnitConversion.feetAndInchesToCm(6, 0)).toBe(183);  // 6'0" = ~182.88cm
            expect(UnitConversion.feetAndInchesToCm(0, 0)).toBe(0);    // Edge case
        });
    });

    describe('cmToFeetAndInches', () => {
        it('converts centimeters to feet and inches correctly', () => {
            const result1 = UnitConversion.cmToFeetAndInches(178);
            expect(result1.feet).toBe(5);
            expect(result1.inches).toBe(10);

            const result2 = UnitConversion.cmToFeetAndInches(183);
            expect(result2.feet).toBe(6);
            expect(result2.inches).toBe(0);
        });
    });

    describe('lbsToKg', () => {
        it('converts pounds to kilograms correctly', () => {
            expect(UnitConversion.lbsToKg(150)).toBe(68);  // 150lbs = ~68.04kg
            expect(UnitConversion.lbsToKg(200)).toBe(91);  // 200lbs = ~90.72kg
            expect(UnitConversion.lbsToKg(0)).toBe(0);     // Edge case
        });
    });

    describe('kgToLbs', () => {
        it('converts kilograms to pounds correctly', () => {
            expect(UnitConversion.kgToLbs(68)).toBe(150);  // 68kg = ~149.91lbs
            expect(UnitConversion.kgToLbs(91)).toBe(201);  // 91kg = ~200.62lbs
            expect(UnitConversion.kgToLbs(0)).toBe(0);     // Edge case
        });
    });

    describe('formatHeight', () => {
        it('formats height in metric by default', () => {
            expect(UnitConversion.formatHeight(178)).toBe('178cm');
        });

        it('formats height in imperial when specified', () => {
            expect(UnitConversion.formatHeight(178, true)).toBe("5'10\"");
        });
    });

    describe('formatWeight', () => {
        it('formats weight in metric by default', () => {
            expect(UnitConversion.formatWeight(68)).toBe('68kg');
        });

        it('formats weight in imperial when specified', () => {
            expect(UnitConversion.formatWeight(68, true)).toBe('150lbs');
        });
    });

    describe('validateHeight', () => {
        it('validates height within acceptable range', () => {
            expect(UnitConversion.validateHeight(100)).toBe(true);  // Min
            expect(UnitConversion.validateHeight(175)).toBe(true);  // Normal
            expect(UnitConversion.validateHeight(250)).toBe(true);  // Max
            expect(UnitConversion.validateHeight(99)).toBe(false);   // Too low
            expect(UnitConversion.validateHeight(251)).toBe(false);  // Too high
        });
    });

    describe('validateWeight', () => {
        it('validates weight within acceptable range', () => {
            expect(UnitConversion.validateWeight(30)).toBe(true);   // Min
            expect(UnitConversion.validateWeight(75)).toBe(true);   // Normal
            expect(UnitConversion.validateWeight(200)).toBe(true);  // Max
            expect(UnitConversion.validateWeight(29)).toBe(false);   // Too low
            expect(UnitConversion.validateWeight(201)).toBe(false);  // Too high
        });
    });
}); 