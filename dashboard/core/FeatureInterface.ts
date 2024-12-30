export interface FeatureInterface {
    register(): void;
    init(): void;
    getIdentifier(): string;
    getMetadata(): Record<string, unknown>;
    isEnabled(): boolean;
} 