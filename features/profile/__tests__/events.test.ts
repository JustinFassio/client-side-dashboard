import { ProfileEvent, ProfileEventPayloads } from '../events/types';
import { mockDashboardEvents } from '../../../dashboard/testing/mocks';
import { ProfileData } from '../types/profile';

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

    it('should emit profile update events', () => {
        const mockProfile: ProfileData = {
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
            physicalMetrics: [{
                type: 'height',
                value: 180,
                unit: 'cm',
                date: new Date().toISOString()
            }, {
                type: 'weight',
                value: 75,
                unit: 'kg',
                date: new Date().toISOString()
            }]
        };

        events.on(ProfileEvent.UPDATE_SUCCESS, mockHandler);
        events.emit(ProfileEvent.UPDATE_SUCCESS, { profile: mockProfile });

        expect(mockHandler).toHaveBeenCalledWith({ profile: mockProfile });
    });

    it('should handle profile update errors', () => {
        const mockError = new Error('Update failed');
        events.on(ProfileEvent.UPDATE_ERROR, mockHandler);
        events.emit(ProfileEvent.UPDATE_ERROR, { error: mockError });

        expect(mockHandler).toHaveBeenCalledWith({ error: mockError });
    });

    it('should emit section change events', () => {
        events.on(ProfileEvent.SECTION_CHANGE, mockHandler);
        events.emit(ProfileEvent.SECTION_CHANGE, { from: 'personal', to: 'metrics' });

        expect(mockHandler).toHaveBeenCalledWith({ from: 'personal', to: 'metrics' });
    });
}); 