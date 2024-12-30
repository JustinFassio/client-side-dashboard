import React from 'react';
import ReactDOM from 'react-dom/client';
import { Events } from '@dashboard/events';
import DashboardShell from '@dashboard/components/DashboardShell';

// Enable event debugging in development
if (process.env.NODE_ENV === 'development') {
    Events.enableDebug();
    console.log('React Development Mode Initialized');
}

// Wait for DOM to be ready
const init = () => {
    const rootElement = document.getElementById('dashboard-root');
    if (!rootElement) {
        console.error('Failed to find root element #dashboard-root');
        return;
    }

    try {
        console.log('Mounting React application...');
        const root = ReactDOM.createRoot(rootElement);
        root.render(
            <React.StrictMode>
                <DashboardShell />
            </React.StrictMode>
        );
        console.log('React application mounted successfully');
    } catch (error) {
        console.error('Failed to mount React application:', error);
    }
};

// Handle initialization
if (document.readyState === 'loading') {
    console.log('Document loading, waiting for DOMContentLoaded...');
    document.addEventListener('DOMContentLoaded', init);
} else {
    console.log('Document already loaded, initializing...');
    init();
} 