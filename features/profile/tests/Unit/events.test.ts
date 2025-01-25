import { ProfileEvent } from '../../events/types';
import { ProfileData } from '../../types/profile';
import { mockDashboardEvents } from '../../../../dashboard/testing/mocks/mocks';

describe('Profile Events', () => {
    let events: ReturnType<typeof mockDashboardEvents>;
    let mockHandler: jest.Mock;

    beforeEach(() => {
        events = mockDashboardEvents();
        mockHandler = jest.fn();
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    it('should emit profile update events', async () => {
        const mockProfile = {
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
            phone: '',
            age: 30,
            dateOfBirth: '1993-01-01',
            gender: 'male',
            dominantSide: 'right',
            medicalClearance: false,
            medicalNotes: '',
            emergencyContactName: '',
            emergencyContactPhone: '',
            injuries: [],
            equipment: [],
            fitnessGoals: ['strength', 'endurance'],
            medicalConditions: [],
            exerciseLimitations: [],
            medications: ''
        } as ProfileData;

        events.on(ProfileEvent.UPDATE_SUCCESS, mockHandler);
        await events.emit(ProfileEvent.UPDATE_SUCCESS, { profile: mockProfile });

        expect(mockHandler).toHaveBeenCalledTimes(1);
        expect(mockHandler).toHaveBeenCalledWith({ profile: mockProfile });
    });

    it('should handle profile update errors', async () => {
        const mockError = new Error('Update failed');
        events.on(ProfileEvent.UPDATE_ERROR, mockHandler);
        await events.emit(ProfileEvent.UPDATE_ERROR, { error: mockError });

        expect(mockHandler).toHaveBeenCalledTimes(1);
        expect(mockHandler).toHaveBeenCalledWith({ error: mockError });
    });

    it('should emit section change events', async () => {
        events.on(ProfileEvent.SECTION_CHANGE, mockHandler);
        await events.emit(ProfileEvent.SECTION_CHANGE, { from: 'personal', to: 'metrics' });

        expect(mockHandler).toHaveBeenCalledTimes(1);
        expect(mockHandler).toHaveBeenCalledWith({ from: 'personal', to: 'metrics' });
    });
}); 