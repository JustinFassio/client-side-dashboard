import React from 'react';
import { Feature, FeatureContext, FeatureMetadata } from '../../dashboard/contracts/Feature';
import { ErrorBoundary } from '../../dashboard/components/ErrorBoundary';
import { WorkoutEvent } from './events';
import { WorkoutProvider } from './contexts/WorkoutContext';
import { WorkoutLayout } from './components/layout';
import { UserProvider } from '../user/context/UserContext';

export class WorkoutGeneratorFeature implements Feature {
    public readonly identifier = 'workout-generator';
    public readonly metadata: FeatureMetadata = {
        name: 'AI Workout Generator',
        description: 'Generate personalized workouts based on your preferences',
        order: 2
    };

    private context: FeatureContext | null = null;

    async register(context: FeatureContext): Promise<void> {
        this.context = context;
        if (context.debug) {
            console.log('[WorkoutGeneratorFeature] Registered');
        }
        return Promise.resolve();
    }

    async init(): Promise<void> {
        if (!this.context) return;

        if (this.context.debug) {
            console.log('[WorkoutGeneratorFeature] Initialized');
        }

        // Dispatch initial load event
        this.context.dispatch('athlete-dashboard')({
            type: WorkoutEvent.FETCH_REQUEST,
            payload: { userId: 0 } // The actual userId will be determined by the WorkoutContext
        });

        return Promise.resolve();
    }

    isEnabled(): boolean {
        return true;
    }

    render({ _userId }: { userId: number }): React.ReactElement | null {
        if (!this.context) {
            console.error('[WorkoutGeneratorFeature] Context not initialized');
            return null;
        }

        return (
            <ErrorBoundary>
                <UserProvider>
                    <WorkoutProvider>
                        <WorkoutLayout context={this.context} />
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