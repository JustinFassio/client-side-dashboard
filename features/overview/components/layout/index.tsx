import React from 'react';
import { FeatureContext } from '../../../../dashboard/contracts/Feature';
import { useUser } from '../../../../dashboard/hooks/useUser';

interface OverviewLayoutProps {
    context: FeatureContext;
}

export const OverviewLayout: React.FC<OverviewLayoutProps> = ({ context }) => {
    const { user } = useUser(context);

    return (
        <div className="overview-layout">
            <header className="overview-header">
                <h1>Dashboard Overview</h1>
            </header>
            <main className="overview-content">
                <section className="overview-summary">
                    <h2>Welcome to Your Dashboard</h2>
                    <p>This is your athlete dashboard overview.</p>
                </section>
                {context.debug && (
                    <section className="overview-debug">
                        <h3>Debug Information</h3>
                        <pre>
                            {JSON.stringify({ userId: user?.id }, null, 2)}
                        </pre>
                    </section>
                )}
            </main>
        </div>
    );
}; 