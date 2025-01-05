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
                    .sort((a, b) => (a.metadata.order || 0) - (b.metadata.order || 0))
                    .map(feature => (
                        <li key={feature.identifier} className="nav-item">
                            <button
                                className={`nav-button ${feature.identifier === currentFeature ? 'active' : ''}`}
                                onClick={() => handleNavigation(feature)}
                                aria-current={feature.identifier === currentFeature ? 'page' : undefined}
                            >
                                <span className="nav-label">{feature.metadata.name}</span>
                                {feature.metadata.description && (
                                    <span className="nav-description">{feature.metadata.description}</span>
                                )}
                            </button>
                        </li>
                    ))}
            </ul>
        </nav>
    );
}; 