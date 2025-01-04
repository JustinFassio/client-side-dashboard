import React from 'react';
import { FeatureContext } from '../../../dashboard/contracts/Feature';
import { ProfileLayout } from '../../../features/profile/components/layout/ProfileLayout';

interface AppProps {
    context: FeatureContext;
}

export const App: React.FC<AppProps> = ({ context }) => {
    return (
        <div className="athlete-dashboard">
            <ProfileLayout context={context} />
        </div>
    );
};