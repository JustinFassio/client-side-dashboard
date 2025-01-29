import React from 'react';
import { Feature, FeatureContext, FeatureMetadata } from '../../dashboard/contracts/Feature';
import { ErrorBoundary } from '../../dashboard/components/ErrorBoundary';
import { WorkoutEvent } from './events';
import { WorkoutProvider } from './contexts/WorkoutContext';
import { UserProvider } from '../user/context/UserContext';
import { WorkoutGeneratorPage } from './pages/WorkoutGeneratorPage';

export class WorkoutGeneratorFeature implements Feature {
    public readonly identifier = 'workout-generator';
    public readonly metadata: FeatureMetadata = {
        name: 'AI Workout Generator',
        description: 'Generate personalized workouts based on your preferences',
        order: 2
    };

    private context: FeatureContext | null = null;

    public async register(context: FeatureContext): Promise<void> {
        this.context = context;
        if (context?.debug) {
            console.log('Workout Generator feature registered');
        }
    }

    public async init(): Promise<void> {
        if (this.context?.debug) {
            console.log('Workout Generator feature initialized');
        }

        // Dispatch initial load event
        this.context?.dispatch('athlete-dashboard')({
            type: WorkoutEvent.FETCH_REQUEST,
            payload: { userId: 0 } // The actual userId will be determined by the WorkoutContext
        });

        return Promise.resolve();
    }

    isEnabled(): boolean {
        return true;
    }

    render({ userId }: { userId: number }): React.ReactElement | null {
        if (!this.context) {
            console.error('[WorkoutGeneratorFeature] Context not initialized');
            return null;
        }

        return (
            <ErrorBoundary>
                <UserProvider>
                    <WorkoutProvider>
                        <WorkoutGeneratorPage />
                    </WorkoutProvider>
                </UserProvider>
            </ErrorBoundary>
        );
    }

    async cleanup(): Promise<void> {
        if (this.context?.debug) {
            console.log('[WorkoutGeneratorFeature] Cleanup');
        }
        this.context = null;
        return Promise.resolve();
    }

    onNavigate(): void {
        if (this.context) {
            this.init();
        }
    }

    onUserChange(): void {
        if (this.context) {
            this.init();
        }
    }
} 