import { Events } from '../../../../dashboard/core/events';
import { ProfileEvents } from '../../events';
import { ProfileData } from '../../types/profile';

describe('Profile Events', () => {
    beforeEach(() => {
        Events.removeAllListeners();
    });

    describe('fetch events', () => {
        it('emits fetch request event', () => {
            const listener = jest.fn();
            Events.on(ProfileEvents.FETCH_REQUEST, listener);

            Events.emit(ProfileEvents.FETCH_REQUEST, { userId: 1 });
            expect(listener).toHaveBeenCalledWith({ userId: 1 });
        });

        it('emits fetch success event with profile data', () => {
            const listener = jest.fn();
            const mockProfile: ProfileData = {
                userId: 1,
                firstName: 'John',
                lastName: 'Doe'
            };

            Events.on(ProfileEvents.FETCH_SUCCESS, listener);
            Events.emit(ProfileEvents.FETCH_SUCCESS, { profile: mockProfile });

            expect(listener).toHaveBeenCalledWith({ profile: mockProfile });
        });

        it('emits fetch error event', () => {
            const listener = jest.fn();
            const error = new Error('Fetch failed');

            Events.on(ProfileEvents.FETCH_ERROR, listener);
            Events.emit(ProfileEvents.FETCH_ERROR, { error });

            expect(listener).toHaveBeenCalledWith({ error });
        });
    });

    describe('update events', () => {
        it('emits update request event', () => {
            const listener = jest.fn();
            const updateData = { firstName: 'Jane' };

            Events.on(ProfileEvents.UPDATE_REQUEST, listener);
            Events.emit(ProfileEvents.UPDATE_REQUEST, { 
                userId: 1, 
                data: updateData 
            });

            expect(listener).toHaveBeenCalledWith({ 
                userId: 1, 
                data: updateData 
            });
        });

        it('emits update success event', () => {
            const listener = jest.fn();
            const mockProfile = {
                userId: 1,
                firstName: 'Jane',
                lastName: 'Doe'
            };

            Events.on(ProfileEvents.UPDATE_SUCCESS, listener);
            Events.emit(ProfileEvents.UPDATE_SUCCESS, { 
                profile: mockProfile,
                updatedFields: ['firstName']
            });

            expect(listener).toHaveBeenCalledWith({ 
                profile: mockProfile,
                updatedFields: ['firstName']
            });
        });
    });

    describe('event cleanup', () => {
        it('removes event listeners correctly', () => {
            const listener = jest.fn();
            Events.on(ProfileEvents.FETCH_REQUEST, listener);
            Events.removeListener(ProfileEvents.FETCH_REQUEST, listener);

            Events.emit(ProfileEvents.FETCH_REQUEST, { userId: 1 });
            expect(listener).not.toHaveBeenCalled();
        });
    });
}); 