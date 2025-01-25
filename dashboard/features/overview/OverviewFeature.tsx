import React from 'react';
import { Feature, FeatureContext, FeatureMetadata, FeatureRenderProps } from '../../contracts/Feature';
import { OverviewService } from './services/OverviewService';
import { Events } from '../../core/events';
import { OverviewLayout } from './components/OverviewLayout';

export class OverviewFeature implements Feature {
    public readonly identifier = 'overview';
    public readonly metadata: FeatureMetadata = {
        name: 'Overview',
        description: 'Dashboard overview showing key metrics and recent activity',
        order: 0
    };

    private context?: FeatureContext;
    private service?: OverviewService;
    private enabled = true;

    async register(context: FeatureContext): Promise<void> {
        this.context = context;
        this.service = new OverviewService(context);

        if (this.context.debug) {
            console.log('[OverviewFeature] Registered with context:', context);
        }
    }

    async init(): Promise<void> {
        if (!this.context || !this.service) {
            throw new Error('Feature not registered');
        }

        try {
            // Initial data fetch
            await this.service.getOverviewData(this.context.userId);
            
            if (this.context.debug) {
                console.log('[OverviewFeature] Initialized successfully');
            }
        } catch (error) {
            console.error('[OverviewFeature] Initialization failed:', error);
            throw error;
        }
    }

    isEnabled(): boolean {
        return this.enabled;
    }

    render({ userId }: FeatureRenderProps): JSX.Element | null {
        if (!this.context || !this.service) {
            console.error('[OverviewFeature] Attempting to render before initialization');
            return null;
        }

        return (
            <OverviewLayout
                userId={userId}
                service={this.service}
                onError={(error) => {
                    Events.emit('feature.error', {
                        identifier: this.identifier,
                        error
                    });
                }}
            />
        );
    }

    async cleanup(): Promise<void> {
        if (this.context?.debug) {
            console.log('[OverviewFeature] Cleaning up');
        }
        // Clean up any subscriptions or side effects
    }

    onNavigate(): void {
        if (this.context?.debug) {
            console.log('[OverviewFeature] Navigation occurred');
        }
        // Handle navigation events
    }

    onUserChange(userId: number): void {
        if (this.context?.debug) {
            console.log('[OverviewFeature] User changed:', userId);
        }
        // Refresh data for new user
        if (this.service) {
            this.service.clearCache();
            this.service.getOverviewData(userId).catch(error => {
                Events.emit('feature.error', {
                    identifier: this.identifier,
                    error
                });
            });
        }
    }
} 