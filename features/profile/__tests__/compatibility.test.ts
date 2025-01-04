import { jest } from '@jest/globals';
import { DashboardEvents } from '../../../dashboard/contracts/Events';
import { LEGACY_PROFILE_EVENTS } from '../types/events';
import { setupProfileEventCompatibility } from '../events/compatibility';
import { mockDashboardEvents } from '../../../dashboard/testing/mocks';

describe('Profile Event Compatibility', () => {
    let mockEvents: jest.Mocked<DashboardEvents>;

    beforeEach(() => {
        mockEvents = mockDashboardEvents() as jest.Mocked<DashboardEvents>;
    });

    it('should map modern events to legacy events', () => {
        setupProfileEventCompatibility(mockEvents);

        const mockProfileData = {
            id: 1,
            username: 'testuser',
            displayName: 'Test User',
            email: 'test@example.com',
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
            physicalMetrics: []
        };

        mockEvents.emit('profile:update-success', mockProfileData);
        expect(mockEvents.emit).toHaveBeenCalledWith(LEGACY_PROFILE_EVENTS.PROFILE_UPDATED, mockProfileData);
    });
}); 