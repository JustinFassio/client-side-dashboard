import { ProfileEvent, ProfileEventPayloads } from '../types';
import { DashboardEvents } from '../../../../dashboard/contracts/Events';
import { mockProfileData, mockDashboardEvents } from '../../../../dashboard/testing/mocks/mocks';
import { ProfileData } from '../../types/profile';

describe('Profile Events', () => {
    let events: DashboardEvents;
    let mockHandler: jest.Mock;

    beforeEach(() => {
        events = mockDashboardEvents() as unknown as DashboardEvents;
        mockHandler = jest.fn();
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    it('should emit fetch request event', () => {
        const payload: ProfileEventPayloads[typeof ProfileEvent.FETCH_REQUEST] = { userId: 1 };
        events.emit(ProfileEvent.FETCH_REQUEST, payload);
        expect(events.emit).toHaveBeenCalledWith(ProfileEvent.FETCH_REQUEST, payload);
    });

    it('should emit fetch success event', () => {
        const mockProfile = {
            ...mockProfileData,
            phone: '',
            dateOfBirth: '',
            dominantSide: '',
            medicalClearance: false,
            medicalNotes: '',
            emergencyContactName: '',
            emergencyContactPhone: '',
            injuries: []
        } as ProfileData;

        const payload: ProfileEventPayloads[typeof ProfileEvent.FETCH_SUCCESS] = { 
            profile: mockProfile
        };
        events.emit(ProfileEvent.FETCH_SUCCESS, payload);
        expect(events.emit).toHaveBeenCalledWith(ProfileEvent.FETCH_SUCCESS, payload);
    });

    it('should emit fetch error event', () => {
        const error = new Error('Failed to fetch profile');
        const payload: ProfileEventPayloads[typeof ProfileEvent.FETCH_ERROR] = { error };
        events.emit(ProfileEvent.FETCH_ERROR, payload);
        expect(events.emit).toHaveBeenCalledWith(ProfileEvent.FETCH_ERROR, payload);
    });

    it('should emit update request event', () => {
        const payload: ProfileEventPayloads[typeof ProfileEvent.UPDATE_REQUEST] = {
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
        const mockProfile = {
            ...mockProfileData,
            phone: '',
            dateOfBirth: '',
            dominantSide: '',
            medicalClearance: false,
            medicalNotes: '',
            emergencyContactName: '',
            emergencyContactPhone: '',
            injuries: []
        } as ProfileData;

        const payload: ProfileEventPayloads[typeof ProfileEvent.UPDATE_SUCCESS] = {
            profile: mockProfile,
            updatedFields: ['firstName', 'lastName']
        };
        events.emit(ProfileEvent.UPDATE_SUCCESS, payload);
        expect(events.emit).toHaveBeenCalledWith(ProfileEvent.UPDATE_SUCCESS, payload);
    });

    it('should emit update error event', () => {
        const error = new Error('Failed to update profile');
        const payload: ProfileEventPayloads[typeof ProfileEvent.UPDATE_ERROR] = {
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
        const payload: ProfileEventPayloads[typeof ProfileEvent.SECTION_CHANGE] = {
            from: 'personal',
            to: 'medical'
        };
        events.emit(ProfileEvent.SECTION_CHANGE, payload);
        expect(events.emit).toHaveBeenCalledWith(ProfileEvent.SECTION_CHANGE, payload);
    });

    it('should handle event listeners correctly', () => {
        const payload = { userId: 1 };
        events.on(ProfileEvent.FETCH_REQUEST, mockHandler);
        events.emit(ProfileEvent.FETCH_REQUEST, payload);
        expect(mockHandler).toHaveBeenCalledWith(payload);
    });

    it('should allow removing event listeners', () => {
        const payload = { userId: 1 };
        events.on(ProfileEvent.FETCH_REQUEST, mockHandler);
        events.off(ProfileEvent.FETCH_REQUEST, mockHandler);
        events.emit(ProfileEvent.FETCH_REQUEST, payload);
        expect(mockHandler).not.toHaveBeenCalled();
    });
}); 