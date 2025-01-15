import React from 'react';
import { UserProvider } from '../../../features/user/context/UserContext';
import { FeatureRegistry } from '../../core/FeatureRegistry';
import { FeatureContext } from '../../contracts/Feature';
import { DashboardShell } from '../DashboardShell';

interface DashboardProviderProps {
    registry: FeatureRegistry;
    context: FeatureContext;
}

/**
 * DashboardProvider wraps the DashboardShell with necessary context providers.
 * This allows us to incrementally add providers without modifying the core DashboardShell.
 */
export const DashboardProvider: React.FC<DashboardProviderProps> = ({ registry, context }) => {
    return (
        <UserProvider>
            <DashboardShell 
                registry={registry} 
                context={context} 
            />
        </UserProvider>
    );
}; 