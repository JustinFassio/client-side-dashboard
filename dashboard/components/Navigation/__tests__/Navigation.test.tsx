import React from 'react';
import { render, fireEvent, screen } from '@testing-library/react';
import { Navigation } from '../index';
import { Events } from '../../../core/events';
import { Feature, FeatureMetadata } from '../../../contracts/Feature';

// Mock the Events module
jest.mock('../../../core/events', () => ({
    Events: {
        emit: jest.fn()
    }
}));

// Create a mock feature class
class MockFeature implements Feature {
    constructor(
        public readonly identifier: string,
        public readonly metadata: FeatureMetadata
    ) {}

    async register() {}
    async init() {}
    isEnabled() { return true; }
    render() { return null; }
    async cleanup() {}
}

describe('Navigation', () => {
    const mockFeatures = [
        new MockFeature('overview', {
            name: 'Overview',
            description: 'Dashboard overview',
            order: 0
        }),
        new MockFeature('profile', {
            name: 'Profile',
            description: 'User profile',
            order: 1
        })
    ];

    beforeEach(() => {
        // Clear URL parameters
        window.history.pushState({}, '', '/');
        // Clear mock calls
        jest.clearAllMocks();
    });

    it('renders all features in correct order', () => {
        render(<Navigation features={mockFeatures} currentFeature="overview" />);
        
        const buttons = screen.getAllByRole('button');
        expect(buttons).toHaveLength(2);
        expect(buttons[0]).toHaveTextContent('Overview');
        expect(buttons[1]).toHaveTextContent('Profile');
    });

    it('marks current feature as active', () => {
        render(<Navigation features={mockFeatures} currentFeature="profile" />);
        
        const profileButton = screen.getByText('Profile').closest('button');
        expect(profileButton).toHaveClass('active');
        expect(profileButton).toHaveAttribute('aria-current', 'page');
    });

    it('emits navigation event and updates URL on feature click', () => {
        render(<Navigation features={mockFeatures} currentFeature="overview" />);
        
        const profileButton = screen.getByText('Profile');
        fireEvent.click(profileButton);

        // Check if event was emitted
        expect(Events.emit).toHaveBeenCalledWith('navigation:changed', {
            identifier: 'profile'
        });

        // Check if URL was updated
        const url = new URL(window.location.href);
        expect(url.searchParams.get('dashboard_feature')).toBe('profile');
    });

    it('displays feature descriptions when available', () => {
        render(<Navigation features={mockFeatures} currentFeature="overview" />);
        
        expect(screen.getByText('Dashboard overview')).toBeInTheDocument();
        expect(screen.getByText('User profile')).toBeInTheDocument();
    });

    it('sorts features by order metadata', () => {
        const unorderedFeatures = [
            new MockFeature('profile', {
                name: 'Profile',
                description: 'User profile',
                order: 2
            }),
            new MockFeature('overview', {
                name: 'Overview',
                description: 'Dashboard overview',
                order: 0
            }),
            new MockFeature('workouts', {
                name: 'Workouts',
                description: 'Workout tracking',
                order: 1
            })
        ];

        render(<Navigation features={unorderedFeatures} currentFeature="overview" />);
        
        const buttons = screen.getAllByRole('button');
        expect(buttons[0]).toHaveTextContent('Overview');
        expect(buttons[1]).toHaveTextContent('Workouts');
        expect(buttons[2]).toHaveTextContent('Profile');
    });
}); 