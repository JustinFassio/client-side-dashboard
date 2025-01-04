import { DebugConfig, EnvironmentConfig, DashboardConfig } from '../../../dashboard/types/config';
import { FeatureData } from '../../../dashboard/types/feature';

interface AthleteDashboardData extends DashboardConfig {
    nonce: string;
    siteUrl: string;
    apiUrl: string;
    userId: number;
}

interface AthleteDashboardFeature extends FeatureData {}

declare global {
    interface Window {
        athleteDashboardData: AthleteDashboardData;
        athleteDashboardFeature?: AthleteDashboardFeature;
        wp: {
            data: {
                dispatch: (storeName: string) => any;
                select: (storeName: string) => any;
                subscribe: (callback: () => void) => () => void;
            };
            hooks: {
                addAction: (event: string, namespace: string, callback: Function) => void;
                removeAction: (event: string, namespace: string, callback: Function) => void;
            };
        };
    }
}

export {}; 