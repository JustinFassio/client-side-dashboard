import { ProfileData, UserProfile } from '../../types/profile';

export const mockProfileData: ProfileData = {
    id: 3,
    username: 'testuser',
    email: 'test@example.com',
    displayName: 'Test User',
    firstName: 'Test',
    lastName: 'User',
    nickname: 'testuser',
    roles: ['subscriber'],
    heightCm: 175,
    weightKg: 70,
    experienceLevel: 'intermediate',
    medicalConditions: [],
    exerciseLimitations: [],
    medications: '',
    medicalClearance: true,
    medicalNotes: '',
    phone: '123-456-7890',
    age: 30,
    dateOfBirth: '1990-01-01',
    gender: 'prefer-not-to-say',
    dominantSide: 'right',
    emergencyContactName: 'Emergency Contact',
    emergencyContactPhone: '911',
    injuries: []
};

export const mockEmptyProfile: ProfileData = {
    id: 0,
    username: '',
    email: '',
    displayName: '',
    firstName: '',
    lastName: '',
    nickname: '',
    roles: [],
    heightCm: 0,
    weightKg: 0,
    experienceLevel: 'beginner',
    medicalConditions: [],
    exerciseLimitations: [],
    medications: '',
    medicalClearance: false,
    medicalNotes: '',
    phone: '',
    age: 0,
    dateOfBirth: '',
    gender: '',
    dominantSide: '',
    emergencyContactName: '',
    emergencyContactPhone: '',
    injuries: []
};

export const mockIncompleteProfile: ProfileData = {
    ...mockEmptyProfile,
    username: 'incomplete',
    email: 'incomplete@example.com',
    displayName: 'Incomplete User'
};

export const mockInvalidProfile: ProfileData = {
    ...mockEmptyProfile,
    email: 'invalid-email'
};

export const mockUserData = {
    id: 3,
    profile: mockProfileData
};

export const mockProfileStates = {
    loading: {
        isComplete: false,
        isLoading: true,
        error: null,
        data: null
    },
    error: {
        isComplete: true,
        isLoading: false,
        error: {
            code: 'FETCH_ERROR',
            message: 'Failed to fetch profile',
            status: 500
        },
        data: null
    },
    success: {
        isComplete: true,
        isLoading: false,
        error: null,
        data: mockProfileData
    },
    incomplete: {
        isComplete: true,
        isLoading: false,
        error: null,
        data: mockIncompleteProfile
    },
    invalid: {
        isComplete: true,
        isLoading: false,
        error: null,
        data: mockInvalidProfile
    }
}; 