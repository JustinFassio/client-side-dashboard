import React from 'react';
import { FeatureRegistry } from '../../core/FeatureRegistry';
import { FeatureRouter } from '../FeatureRouter';
import './DashboardShell.css';

interface DashboardShellProps {
    registry: FeatureRegistry;
}

export const DashboardShell: React.FC<DashboardShellProps> = ({ registry }) => {
    return (
        <div className="dashboard-shell">
            <nav className="dashboard-nav">
                {registry.getEnabledFeatures().map(feature => (
                    <a
                        key={feature.identifier}
                        href={`?dashboard_feature=${feature.identifier}`}
                        className={`nav-item ${window.athleteDashboardFeature?.name === feature.identifier ? 'active' : ''}`}
                    >
                        {feature.metadata.icon}
                        <span>{feature.metadata.name}</span>
                    </a>
                ))}
            </nav>
            <main className="dashboard-content">
                <FeatureRouter registry={registry} />
            </main>
        </div>
    );
}; 