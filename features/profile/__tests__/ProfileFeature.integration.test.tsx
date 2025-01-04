import React from 'react';
import { render, screen } from '@testing-library/react';
import { ProfileFeature } from '../ProfileFeature';
import { FeatureContext } from '../../../dashboard/contracts/Feature';
import { ProfileEvent } from '../events';

describe('ProfileFeature Integration', () => {
    let feature: ProfileFeature;
    let mockContext: FeatureContext;

    beforeEach(() => {
        mockContext = {
            userId: 1,
            nonce: 'test-nonce',
            apiUrl: 'http://test.local/wp-json',
            debug: false,
            navigate: jest.fn(),
            isEnabled: jest.fn(() => true),
            dispatch: jest.fn(() => jest.fn()),
            addListener: jest.fn(),
            unsubscribe: jest.fn()
        };
        feature = new ProfileFeature();
    });

    it('should register and initialize correctly', async () => {
        await feature.register(mockContext);
        await feature.init();
        expect(mockContext.dispatch).toHaveBeenCalledWith('athlete-dashboard');
        expect(mockContext.dispatch('athlete-dashboard')).toHaveBeenCalledWith({
            type: ProfileEvent.FETCH_REQUEST,
            payload: { userId: mockContext.userId }
        });
    });

    it('should not render when not registered', () => {
        const element = feature.render();
        expect(element).toBeNull();
    });

    it('should render profile layout when registered', async () => {
        await feature.register(mockContext);
        const element = feature.render();
        expect(element).not.toBeNull();
    });

    it('should cleanup correctly', async () => {
        await feature.register(mockContext);
        await feature.cleanup();
        const element = feature.render();
        expect(element).toBeNull();
    });

    it('should have correct metadata', () => {
        expect(feature.identifier).toBe('profile');
        expect(feature.metadata.name).toBe('Profile');
        expect(feature.metadata.description).toBe('Manage your athlete profile');
    });
}); 