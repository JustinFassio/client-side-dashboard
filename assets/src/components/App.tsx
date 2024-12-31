import { createElement, useState, useEffect } from '@wordpress/element';
import { DashboardShell } from '@dashboard/components/DashboardShell';

export default function App() {
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        // Simulate initialization
        const timer = setTimeout(() => {
            setIsLoading(false);
        }, 500);

        return () => clearTimeout(timer);
    }, []);

    if (isLoading) {
        return (
            <div className="athlete-dashboard loading">
                <div>Loading...</div>
            </div>
        );
    }

    return <DashboardShell />;
}