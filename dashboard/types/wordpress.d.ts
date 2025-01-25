declare global {
    interface Window {
        wp: {
            data: {
                dispatch: (storeName: string) => any;
                select: (storeName: string) => any;
                subscribe: (callback: () => void) => () => void;
            }
        };
        athleteDashboardData: {
            nonce: string;
            siteUrl: string;
            apiUrl: string;
            userId: number;
        };
    }
}

export {}; 