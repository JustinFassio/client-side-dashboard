import { createElement, render } from '@wordpress/element';
import { DashboardShell } from '../../dashboard/components/DashboardShell';
import { FeatureRegistry } from '../../dashboard/core/FeatureRegistry';
import { FeatureContext } from '../../dashboard/contracts/Feature';
import { ProfileFeature } from '../../features/profile/ProfileFeature';
import '../../dashboard/styles/main.css';

declare global {
    interface Window {
        athleteDashboardData: {
            nonce: string;
            apiUrl: string;
            debug?: boolean;
        };
        athleteDashboardFeature?: {
            name: string;
        };
    }
}

// Create the feature context with required properties
const context: FeatureContext = {
    dispatch: (scope: string) => (action: any) => {
        // Implement dispatch logic if needed
        console.log(`Dispatching action to ${scope}:`, action);
    },
    apiUrl: window.athleteDashboardData.apiUrl,
    nonce: window.athleteDashboardData.nonce,
    debug: window.athleteDashboardData.debug || false
};

// Initialize registry with context
const registry = new FeatureRegistry(context);

// Initialize the dashboard
const initializeDashboard = async () => {
    try {
        // Register the profile feature
        await registry.register(new ProfileFeature());

        // Render the dashboard
        const container = document.getElementById('athlete-dashboard');
        if (container) {
            render(createElement(DashboardShell, { registry, context }), container);
        }
    } catch (error) {
        console.error('Failed to initialize dashboard:', error);
    }
};

initializeDashboard();