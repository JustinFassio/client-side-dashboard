import React from 'react';
import { createElement } from '@wordpress/element';
import { Feature, FeatureContext } from '../../dashboard/contracts/Feature';
import { Home } from 'lucide-react';

const OverviewComponent: React.FC = () => {
    return (
        <div className="overview-feature">
            <h1>Welcome to Your Dashboard</h1>
            <p>Select a feature from the navigation to get started.</p>
        </div>
    );
};

export class OverviewFeature implements Feature {
    public readonly identifier = 'overview';
    public readonly metadata = {
        name: 'Overview',
        description: 'Dashboard Overview',
        icon: createElement(Home, {
            size: 36,
            strokeWidth: 1.5,
            className: 'nav-feature-icon',
            color: '#ddff0e'
        }),
        order: 0
    };

    private context: FeatureContext | null = null;

    async register(context: FeatureContext): Promise<void> {
        this.context = context;
    }

    async init(): Promise<void> {
        // No initialization needed
    }

    cleanup(): void {
        this.context = null;
    }

    isEnabled(): boolean {
        return true;
    }

    render(): JSX.Element {
        return createElement(OverviewComponent);
    }

    onNavigate(): void {
        // No navigation handling needed
    }
} 