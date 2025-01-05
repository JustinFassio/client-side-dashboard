import { DashboardEvents } from '../../core/events';
import { FeatureContext } from '../../contracts/Feature';

export interface ProfileData {
    id: number;
    username: string;
    email: string;
    displayName: string;
    firstName: string;
    lastName: string;
    age: number;
    gender: string;
    height: number;
    weight: number;
    fitnessLevel: string;
    activityLevel: string;
    medicalConditions: string[];
    exerciseLimitations: string[];
    medications: string;
    physicalMetrics: Array<{
        type: string;
        value: number;
        unit: string;
        date: string;
    }>;
}

export const mockProfileData: ProfileData = {
    id: 1,
    username: 'testuser',
    email: 'test@example.com',
    displayName: 'Test User',
    firstName: 'Test',
    lastName: 'User',
    age: 25,
    gender: 'male',
    height: 180,
    weight: 75,
    fitnessLevel: 'intermediate',
    activityLevel: 'moderately_active',
    medicalConditions: [],
    exerciseLimitations: [],
    medications: '',
    physicalMetrics: [
        {
            type: 'height',
            value: 180,
            unit: 'cm',
            date: '2023-01-01'
        },
        {
            type: 'weight',
            value: 75,
            unit: 'kg',
            date: '2023-01-01'
        }
    ]
};

export const mockFeatureContext = (): FeatureContext => ({
    nonce: 'test-nonce',
    apiUrl: 'http://test.local/wp-json',
    debug: false,
    dispatch: (scope: string) => (action: any) => {
        console.log(`Mock dispatch to ${scope}:`, action);
    }
});

export const mockDashboardEvents = (): DashboardEvents => ({
    emit: jest.fn(),
    on: jest.fn(),
    off: jest.fn(),
    removeAllListeners: jest.fn(),
    removeListener: jest.fn(),
    addListener: jest.fn(),
    once: jest.fn(),
    eventNames: jest.fn(),
    getMaxListeners: jest.fn(),
    listenerCount: jest.fn(),
    listeners: jest.fn(),
    prependListener: jest.fn(),
    prependOnceListener: jest.fn(),
    rawListeners: jest.fn(),
    setMaxListeners: jest.fn()
}); 