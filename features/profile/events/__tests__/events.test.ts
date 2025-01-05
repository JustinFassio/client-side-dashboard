import { ProfileEvent, ProfileEventPayloads } from '../types';
import { mockProfileData, mockDashboardEvents } from '../../../../dashboard/testing/mocks/mocks';
import { DashboardEvents } from '../../../../dashboard/events';

describe('Profile Events', () => {
    let events: DashboardEvents;

    beforeEach(() => {
        events = mockDashboardEvents();
    });

    it('should emit fetch request event', () => {
        const payload = { userId: 1 };
        events.emit(ProfileEvent.FETCH_REQUEST, payload);
        expect(events.emit).toHaveBeenCalledWith(ProfileEvent.FETCH_REQUEST, payload);
    });

    it('should emit fetch success event', () => {
        const payload = { profile: mockProfileData };
        events.emit(ProfileEvent.FETCH_SUCCESS, payload);
        expect(events.emit).toHaveBeenCalledWith(ProfileEvent.FETCH_SUCCESS, payload);
    });

    it('should emit fetch error event', () => {
        const error = new Error('Failed to fetch profile');
        const payload = { error };
        events.emit(ProfileEvent.FETCH_ERROR, payload);
        expect(events.emit).toHaveBeenCalledWith(ProfileEvent.FETCH_ERROR, payload);
    });

    it('should emit update request event', () => {
        const payload = {
            userId: 1,
            data: {
                firstName: 'John',
                lastName: 'Doe'
            }
        };
        events.emit(ProfileEvent.UPDATE_REQUEST, payload);
        expect(events.emit).toHaveBeenCalledWith(ProfileEvent.UPDATE_REQUEST, payload);
    });

    it('should emit update success event', () => {
        const payload = {
            profile: mockProfileData,
            updatedFields: ['firstName', 'lastName']
        };
        events.emit(ProfileEvent.UPDATE_SUCCESS, payload);
        expect(events.emit).toHaveBeenCalledWith(ProfileEvent.UPDATE_SUCCESS, payload);
    });

    it('should emit update error event', () => {
        const error = new Error('Failed to update profile');
        const payload = {
            error,
            attemptedData: {
                firstName: 'John',
                lastName: 'Doe'
            }
        };
        events.emit(ProfileEvent.UPDATE_ERROR, payload);
        expect(events.emit).toHaveBeenCalledWith(ProfileEvent.UPDATE_ERROR, payload);
    });

    it('should emit section change event', () => {
        const payload = {
            from: 'personal',
            to: 'medical'
        };
        events.emit(ProfileEvent.SECTION_CHANGE, payload);
        expect(events.emit).toHaveBeenCalledWith(ProfileEvent.SECTION_CHANGE, payload);
    });
}); 