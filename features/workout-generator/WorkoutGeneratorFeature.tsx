import React from 'react';
import { Feature, FeatureMetadata } from '../../dashboard/contracts/Feature';
import { WorkoutGenerator } from './components/WorkoutGenerator';
import { WorkoutProvider } from './contexts/workout-context';

export class WorkoutGeneratorFeature implements Feature {
    public readonly identifier = 'workout-generator';
    public readonly metadata: FeatureMetadata = {
        name: 'AI Workout Generator',
        description: 'Generate personalized workouts based on your preferences',
        order: 2 // After Overview (0) and Profile (1)
    };

    async register(): Promise<void> {
        // Registration logic if needed
        return Promise.resolve();
    }

    async init(): Promise<void> {
        // Initialization logic if needed
        return Promise.resolve();
    }

    isEnabled(): boolean {
        return true; // Feature is always enabled for now
    }

    render({ userId }: { userId: number }): React.Element | null {
        return (
            <WorkoutProvider>
                <WorkoutGenerator userId={userId} />
            </WorkoutProvider>
        ) as React.Element;
    }

    async cleanup(): Promise<void> {
        // Cleanup logic if needed
        return Promise.resolve();
    }
} 