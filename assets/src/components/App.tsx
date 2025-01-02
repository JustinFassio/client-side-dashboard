import React, { useEffect, useState } from 'react';
import { UserProvider, RequireAuth } from '@features/user/context/UserContext';
import { ProfileProvider } from '@features/profile/context/ProfileContext';
import { DashboardShell } from '@dashboard/components/DashboardShell';
import { Config } from '@dashboard/core/config';
import { FeatureRegistry } from '@dashboard/core/FeatureRegistry';
import features from '../features';

export function App() {
    Config.log('Initializing App', 'core');
    const [registry, setRegistry] = useState<FeatureRegistry | null>(null);

    useEffect(() => {
        const initRegistry = async () => {
            const context = {
                dispatch: window.wp.data.dispatch,
                userId: window.athleteDashboardData.userId,
                nonce: window.athleteDashboardData.nonce,
                apiUrl: window.athleteDashboardData.apiUrl
            };
            const newRegistry = new FeatureRegistry(context);
            
            // Register all features
            for (const feature of features) {
                await newRegistry.register(feature);
            }
            
            setRegistry(newRegistry);
        };

        initRegistry();
    }, []);

    if (!registry) {
        return <div>Loading...</div>;
    }

    return (
        <UserProvider>
            <RequireAuth>
                <ProfileProvider>
                    <DashboardShell registry={registry} />
                </ProfileProvider>
            </RequireAuth>
        </UserProvider>
    );
}