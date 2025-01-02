import { createElement } from '@wordpress/element';
import { createRoot } from '@wordpress/element';
import { DashboardShell } from '../../dashboard/components/DashboardShell/index';
import { FeatureRegistry } from '../../dashboard/core/FeatureRegistry';
import features from './features';
import '../../dashboard/styles/main.css';

const initializeDashboard = async () => {
    // Wait for WordPress and dashboard data to be ready
    if (!window.wp?.data?.dispatch || !window.athleteDashboardData) {
        console.warn('Waiting for WordPress data to be available...');
        setTimeout(initializeDashboard, 100);
        return;
    }

    // Verify feature data
    if (!window.athleteDashboardFeature) {
        console.error('Feature data not available. Check wp_localize_script initialization.');
        return;
    }

    console.log('Initializing dashboard with feature:', window.athleteDashboardFeature.name);

    const context = {
        dispatch: window.wp.data.dispatch,
        userId: window.athleteDashboardData.userId,
        nonce: window.athleteDashboardData.nonce,
        apiUrl: window.athleteDashboardData.apiUrl
    };

    try {
        // Initialize feature registry
        const registry = new FeatureRegistry(context);

        // Register all features
        for (const feature of features) {
            await registry.register(feature);
        }

        // Verify the current feature exists
        const currentFeature = registry.getFeature(window.athleteDashboardFeature.name);
        if (!currentFeature) {
            console.warn(`Feature "${window.athleteDashboardFeature.name}" not found in registry.`);
        }

        // Log available features for debugging
        console.log('Registered features:', registry.getEnabledFeatures().map(f => f.identifier));

        // Render the dashboard
        const root = document.getElementById('dashboard-root');
        if (root) {
            createRoot(root).render(<DashboardShell registry={registry} />);
        } else {
            console.error('Dashboard root element not found');
        }
    } catch (error) {
        console.error('Failed to initialize dashboard:', error);
    }
};

// Start initialization
initializeDashboard();