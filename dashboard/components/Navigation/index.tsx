import React from 'react';
import { Feature } from '../../contracts/Feature';
import { Events } from '../../core/events';
import './Navigation.css';

interface NavigationProps {
    features: Feature[];
    currentFeature?: string;
}

export const Navigation: React.FC<NavigationProps> = ({ features, currentFeature }) => {
    const handleNavigation = (feature: Feature) => {
        if (window.athleteDashboardData.debug) {
            console.log('[Navigation] Navigating to feature:', {
                from: currentFeature,
                to: feature.identifier,
                metadata: feature.metadata
            });
        }

        // Emit navigation event
        Events.emit('navigation:changed', { identifier: feature.identifier });
        
        // Update URL with new feature
        const url = new URL(window.location.href);
        url.searchParams.set('dashboard_feature', feature.identifier);
        window.history.pushState({}, '', url.toString());
    };

    return (
        <nav className="dashboard-nav">
            <div className="nav-header">
                <h2>Dashboard</h2>
            </div>
            <ul className="nav-list">
                {features
                    .sort((a, b) => {
                        // Ensure Overview is always first
                        if (a.identifier === 'overview') return -1;
                        if (b.identifier === 'overview') return 1;
                        // Then sort by order
                        return (a.metadata.order || 0) - (b.metadata.order || 0);
                    })
                    .map(feature => {
                        const isActive = feature.identifier === currentFeature;
                        if (window.athleteDashboardData.debug) {
                            console.log('[Navigation] Rendering feature:', {
                                id: feature.identifier,
                                active: isActive,
                                metadata: feature.metadata
                            });
                        }
                        return (
                            <li key={feature.identifier} className="nav-item">
                                <button
                                    className={`nav-button ${isActive ? 'active' : ''}`}
                                    onClick={() => handleNavigation(feature)}
                                    aria-current={isActive ? 'page' : undefined}
                                >
                                    <span className="nav-label">{feature.metadata.name}</span>
                                    {feature.metadata.description && (
                                        <span className="nav-description">{feature.metadata.description}</span>
                                    )}
                                </button>
                            </li>
                        );
                    })}
            </ul>
        </nav>
    );
}; 