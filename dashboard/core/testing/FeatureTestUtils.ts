import { Feature } from '../../contracts/Feature';

export class ErrorFeature implements Feature {
    public identifier = 'error-test';
    public isInitialized = false;

    constructor(private errorType: 'init' | 'render' = 'render') {}

    async init(): Promise<void> {
        if (this.errorType === 'init') {
            throw new Error('Simulated initialization error');
        }
        this.isInitialized = true;
    }

    render(): React.ReactNode {
        if (this.errorType === 'render') {
            throw new Error('Simulated render error');
        }
        return null;
    }

    cleanup(): void {}
    
    isEnabled(): boolean {
        return true;
    }

    onNavigate(): void {}
}

export class DisabledFeature implements Feature {
    public identifier = 'disabled-test';
    public isInitialized = false;

    async init(): Promise<void> {
        this.isInitialized = true;
    }

    render(): React.ReactNode {
        return null;
    }

    cleanup(): void {}
    
    isEnabled(): boolean {
        return false;
    }

    onNavigate(): void {}
}

export const simulateSlowFeature = async (duration: number = 2000): Promise<void> => {
    return new Promise(resolve => setTimeout(resolve, duration));
}; 