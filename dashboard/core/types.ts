export interface FeatureContext {
    userId: number;
    nonce: string;
    apiUrl: string;
    debug?: boolean;
    navigate: (path: string) => void;
    isEnabled: () => boolean;
    dispatch: (namespace: string) => (event: any) => void;
    addListener: (event: string, callback: Function) => void;
    unsubscribe: (event: string, callback: Function) => void;
} 