import { createElement } from '@wordpress/element';
import { FeatureRegistry } from '../../dashboard/core/FeatureRegistry';
import { Config as _Config } from '../../dashboard/core/config';
import { DashboardRoot } from './DashboardRoot';
import { ProfileService } from '../../features/profile/services/ProfileService';
import { ApiClient } from '../../dashboard/services/api';
import '../../dashboard/styles/main.css';

interface DashboardContext {
    apiUrl: string;
    nonce: string;
    debug: boolean;
    userId: number;
    dispatch: Window['wp']['data']['dispatch'];
}

async function initializeDashboard() {
    const context: DashboardContext = {
        apiUrl: window.athleteDashboardData.apiUrl,
        nonce: window.athleteDashboardData.nonce,
        debug: Boolean(window.athleteDashboardData.debug),
        userId: Number(window.athleteDashboardData.userId),
        dispatch: (scope: string) => (action) => {
            if (window.athleteDashboardData.debug) {
                console.log(`Dispatching action to ${scope}:`, action);
            }
        }
    };

    try {
        if (context.debug) {
            console.log('[Dashboard] Initializing with context:', context);
            console.log('[Dashboard] Current feature:', window.athleteDashboardData.feature);
        }

        // Initialize API client and profile service
        const apiClient = new ApiClient({
            baseURL: context.apiUrl,
            headers: {
                'X-WP-Nonce': context.nonce
            }
        });
        const profileService = new ProfileService(apiClient, context.nonce);
        window.profileService = profileService; // For debugging

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
            console.log('[Dashboard] Debug mode enabled');
            window.registry = registry;
        }

        const root = document.getElementById('athlete-dashboard');
        if (!root) {
            throw new Error('Dashboard root element not found');
        }

        const element = createElement(DashboardRoot, { registry });
        window.wp.element.render(element, root);

    } catch (error) {
        console.error('[Dashboard] Failed to initialize:', error);
    }
}

initializeDashboard();