import React from 'react';
import { UserProvider, RequireAuth } from '@features/user/context/UserContext';
import { ProfileProvider } from '@features/profile/context/ProfileContext';
import { DashboardShell } from '@dashboard/components/DashboardShell';
import { Config } from '@dashboard/core/config';

export function App() {
    Config.log('Initializing App', 'core');

    return (
        <UserProvider>
            <RequireAuth>
                <ProfileProvider>
                    <DashboardShell />
                </ProfileProvider>
            </RequireAuth>
        </UserProvider>
    );
}