// Define action type for dispatch
interface DashboardAction {
    type: string;
    payload?: unknown;
}

declare global {
    interface Window {
        wp: {
            data: {
                dispatch: (storeName: string) => (action: DashboardAction) => void;
                select: (storeName: string) => unknown;
                subscribe: (callback: () => void) => () => void;
            };
            element: {
                render: (element: React.ReactElement, container: HTMLElement) => void;
            };
        };
        athleteDashboardData: {
            apiUrl: string;
            nonce: string;
            siteUrl: string;
            debug: boolean;
            userId: number;
            feature?: {
                name: string;
                label: string;
            };
        };
    }
}

export {}; 