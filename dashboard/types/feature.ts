/**
 * Core feature data interface that defines the structure of a feature
 * in the athlete dashboard system.
 */
export interface FeatureData {
    /** Unique identifier for the feature */
    name: string;
    /** Display label for the feature */
    label: string;
    /** Optional description of the feature's purpose */
    description?: string;
    /** Optional icon for the feature */
    icon?: string | JSX.Element;
    /** Optional ordering priority */
    order?: number;
} 