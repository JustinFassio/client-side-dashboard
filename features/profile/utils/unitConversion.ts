/**
 * Unit conversion utilities for profile measurements
 */

export const UnitConversion = {
    /**
     * Convert feet and inches to centimeters
     */
    feetAndInchesToCm(feet: number, inches: number): number {
        return Math.round((feet * 30.48) + (inches * 2.54));
    },

    /**
     * Convert centimeters to feet and inches
     */
    cmToFeetAndInches(cm: number): { feet: number; inches: number } {
        const totalInches = cm / 2.54;
        const feet = Math.floor(totalInches / 12);
        const inches = Math.round(totalInches % 12);
        return { feet, inches };
    },

    /**
     * Convert pounds to kilograms
     */
    lbsToKg(lbs: number): number {
        return Math.round(lbs * 0.45359237);
    },

    /**
     * Convert kilograms to pounds
     */
    kgToLbs(kg: number): number {
        return Math.round(kg / 0.45359237);
    },

    /**
     * Format height in the user's preferred unit system
     */
    formatHeight(cm: number, useImperial: boolean = false): string {
        if (useImperial) {
            const { feet, inches } = this.cmToFeetAndInches(cm);
            return `${feet}'${inches}"`;
        }
        return `${cm}cm`;
    },

    /**
     * Format weight in the user's preferred unit system
     */
    formatWeight(kg: number, useImperial: boolean = false): string {
        if (useImperial) {
            const lbs = this.kgToLbs(kg);
            return `${lbs}lbs`;
        }
        return `${kg}kg`;
    },

    /**
     * Validate height value in centimeters
     */
    validateHeight(cm: number): boolean {
        return cm >= 100 && cm <= 250;
    },

    /**
     * Validate weight value in kilograms
     */
    validateWeight(kg: number): boolean {
        return kg >= 30 && kg <= 200;
    }
}; 