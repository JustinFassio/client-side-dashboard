import React from 'react';
import { Feature } from '../Feature';
import { FeatureContext } from '../types';

export class ErrorFeature implements Feature {
    public readonly id = 'error';
    public readonly name = 'Error';
    public readonly description = 'Error Feature';
    public isInitialized = false;

    public async initialize(): Promise<void> {
        throw new Error('Feature initialization failed');
    }

    public render(): React.ReactElement | null {
        return null;
    }

    public cleanup(): void {
        // No cleanup needed
    }
}

export class DisabledFeature implements Feature {
    public readonly id = 'disabled';
    public readonly name = 'Disabled';
    public readonly description = 'Disabled Feature';
    public isInitialized = false;

    public async initialize(_context: FeatureContext): Promise<void> {
        this.isInitialized = true;
    }

    public render(): React.ReactElement | null {
        return null;
    }

    public cleanup(): void {
        this.isInitialized = false;
    }
}

export const createMockContext = (overrides: Partial<FeatureContext> = {}): FeatureContext => ({
    userId: 1,
    nonce: 'test-nonce',
    apiUrl: 'http://test.local/wp-json',
    debug: false,
    navigate: jest.fn(),
    isEnabled: jest.fn(() => true),
    dispatch: jest.fn(() => jest.fn()),
    addListener: jest.fn(),
    unsubscribe: jest.fn(),
    ...overrides
}); 