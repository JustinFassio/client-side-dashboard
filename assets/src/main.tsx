import { createElement } from '@wordpress/element';
import { FeatureRegistry } from '../../dashboard/core/FeatureRegistry';
import { DashboardShell } from '../../dashboard/components/DashboardShell';
import { Config } from '../../dashboard/core/config';
import '../../dashboard/styles/main.css';

declare global {
    interface Window {
        athleteDashboardData: {
            apiUrl: string;
            nonce: string;
            debug: boolean;
            userId: number;
            feature?: {
                name: string;
                label: string;
            };
        };
    }
}

async function initializeDashboard() {
    const context = {
        apiUrl: window.athleteDashboardData.apiUrl,
        nonce: window.athleteDashboardData.nonce,
        debug: window.athleteDashboardData.debug,
        userId: window.athleteDashboardData.userId,
        dispatch: (scope: string) => (action: any) => {
            console.log(`Dispatching action to ${scope}:`, action);
        }
    };

    try {
        console.log('[Dashboard] Initializing with context:', context);
        console.log('[Dashboard] Current feature:', window.athleteDashboardData.feature);

        const registry = new FeatureRegistry(context);

        // Dynamically import features
        const { OverviewFeature } = await import('../../features/overview/OverviewFeature');
        const { ProfileFeature } = await import('../../features/profile/ProfileFeature');
        const { WorkoutGeneratorFeature } = await import('../../features/workout-generator/WorkoutGeneratorFeature');
        const { EquipmentManagerFeature } = await import('../../features/equipment/EquipmentManagerFeature');

        // Register features in correct order
        await registry.register(new OverviewFeature());
        await registry.register(new ProfileFeature());
        await registry.register(new WorkoutGeneratorFeature());
        await registry.register(new EquipmentManagerFeature());

        console.log('[Dashboard] Registered features:', registry.getAllFeatures().map(f => ({
            id: f.identifier,
            enabled: f.isEnabled(),
            metadata: f.metadata
        })));

        if (context.debug) {
            console.log('[Dashboard] Feature registration complete');
        }

        // Initialize dashboard
        const container = document.getElementById('athlete-dashboard');
        if (!container) {
            throw new Error('Dashboard container not found');
        }

        const root = createElement(DashboardShell, {
            registry,
            context
        });

        // @ts-ignore (wp.element.render is available)
        wp.element.render(root, container);

    } catch (error) {
        console.error('[Dashboard] Failed to initialize:', error);
        const container = document.getElementById('athlete-dashboard');
        if (container) {
            container.innerHTML = `
                <div class="dashboard-error">
                    <h3>Dashboard Error</h3>
                    <p>Failed to initialize the dashboard. Please try refreshing the page.</p>
                    <pre>${error instanceof Error ? error.message : 'Unknown error'}</pre>
                </div>
            `;
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', initializeDashboard);