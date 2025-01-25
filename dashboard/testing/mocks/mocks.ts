import { DashboardEvents } from '../../core/events';
import { FeatureContext } from '../../contracts/Feature';

export interface ProfileData {
    id: number;
    username: string;
    email: string;
    displayName: string;
    firstName: string;
    lastName: string;
    nickname: string;
    roles: string[];
    heightCm: number;
    weightKg: number;
    experienceLevel: 'beginner' | 'intermediate' | 'advanced';
    medicalConditions: string[];
    exerciseLimitations: string[];
    medications: string;
    medicalClearance: boolean;
    medicalNotes: string;
    phone: string;
    age: number;
    dateOfBirth: string;
    gender: 'male' | 'female' | 'other' | '';
    dominantSide: 'left' | 'right' | '';
    emergencyContactName: string;
    emergencyContactPhone: string;
    injuries: Array<{
        id: string;
        name: string;
        details: string;
        type: string;
        description: string;
        date: string;
        severity: string;
        status: string;
        isCustom?: boolean;
    }>;
    equipment?: string[];
    fitnessGoals?: string[];
}

export const mockProfileData: ProfileData = {
    id: 1,
    username: 'testuser',
    email: 'test@example.com',
    displayName: 'Test User',
    firstName: 'Test',
    lastName: 'User',
    nickname: 'tester',
    roles: ['subscriber'],
    heightCm: 180,
    weightKg: 75,
    experienceLevel: 'intermediate',
    medicalConditions: [],
    exerciseLimitations: [],
    medications: '',
    medicalClearance: false,
    medicalNotes: '',
    phone: '123-456-7890',
    age: 25,
    dateOfBirth: '1998-01-01',
    gender: 'male',
    dominantSide: 'right',
    emergencyContactName: '',
    emergencyContactPhone: '',
    injuries: [],
    equipment: [],
    fitnessGoals: ['strength', 'endurance']
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