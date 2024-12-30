import React, { useEffect } from 'react';
import './DashboardShell.scss';

const DashboardShell: React.FC = () => {
    useEffect(() => {
        console.log('DashboardShell mounted');
        
        // Remove loading message
        const loadingElement = document.getElementById('dev-loading');
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
    }, []);

    return (
        <div className="dashboard-shell">
            <h1>Welcome to Athlete Dashboard</h1>
            {process.env.NODE_ENV === 'development' && (
                <div className="debug-info">
                    <p>React component rendered successfully</p>
                    <p>Environment: {process.env.NODE_ENV}</p>
                </div>
            )}
        </div>
    );
};

export default DashboardShell; 