import { createElement } from '@wordpress/element';
import { createRoot } from '@wordpress/element';
import { DashboardShell } from '../../dashboard/components/DashboardShell/index';
import { FeatureRegistry } from '../../dashboard/core/FeatureRegistry';
import features from './features';
import '../../dashboard/styles/main.css';

const initializeDashboard = async () => {
    // Wait for WordPress to be ready
    if (!window.wp?.data?.dispatch || !window.athleteDashboardData) {
        setTimeout(initializeDashboard, 100);
        return;
    }

    const context = {
        dispatch: window.wp.data.dispatch,
        userId: window.athleteDashboardData.userId,
        nonce: window.athleteDashboardData.nonce,
        apiUrl: window.athleteDashboardData.apiUrl
    };

    // Initialize feature registry
    const registry = new FeatureRegistry(context);

    // Register all features
    for (const feature of features) {
        await registry.register(feature);
    }

    // Render the dashboard
    const root = document.getElementById('dashboard-root');
    if (root) {
        createRoot(root).render(<DashboardShell registry={registry} />);
    }
};

// Start initialization
initializeDashboard();