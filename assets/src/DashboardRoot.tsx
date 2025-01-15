import React from 'react';
import { DashboardShell } from '../../dashboard/components/DashboardShell';
import { UserProvider } from '../../features/user/context/UserContext';
import { FeatureRegistry } from '../../dashboard/core/FeatureRegistry';
import { FeatureContext } from '../../dashboard/contracts/Feature';

interface DashboardRootProps {
    registry: FeatureRegistry;
    context: FeatureContext;
}

export const DashboardRoot: React.FC<DashboardRootProps> = ({ registry, context }) => {
    return (
        <UserProvider>
            <DashboardShell registry={registry} context={context} />
        </UserProvider>
    );
}; 