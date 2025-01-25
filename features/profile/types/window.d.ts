import { DashboardConfig } from '../../../dashboard/types/config';

interface AthleteDashboardData extends DashboardConfig {
    nonce: string;
    siteUrl: string;
    apiUrl: string;
    userId: number;
}

declare global {
    interface Window {
        athleteDashboardData: AthleteDashboardData;
        wp: {
            data: {
                dispatch: (storeName: string) => any;
                select: (storeName: string) => any;
                subscribe: (callback: () => void) => () => void;
            };
            hooks: {
                addAction: (event: string, namespace: string, callback: (...args: unknown[]) => void) => void;
                removeAction: (event: string, namespace: string, callback: (...args: unknown[]) => void) => void;
            };
        };
    }
}

export {}; 