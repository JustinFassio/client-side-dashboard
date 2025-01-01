import { Events } from '../../../dashboard/core/events';
import { ProfileEvents, emitProfileEvent, onProfileEvent, offProfileEvent } from '../events';
import { ProfileData } from '../types/profile';

describe('Profile Events', () => {
    beforeEach(() => {
        // Setup before each test
        jest.spyOn(console, 'log').mockImplementation(() => {});
    });

    afterEach(() => {
        // Cleanup after each test
        jest.clearAllMocks();
    });

    describe('Event Names', () => {
        it('should use WordPress-compatible event names', () => {
            const eventNames = Object.values(ProfileEvents);
            eventNames.forEach(name => {
                expect(name).toMatch(/^athlete_dashboard_profile_[a-z_]+$/);
            });
        });
    });

    describe('Event Emission', () => {
        it('should emit and receive fetch request event', (done) => {
            onProfileEvent(ProfileEvents.FETCH_REQUEST, (data) => {
                expect(data).toBeUndefined();
                done();
            });

            emitProfileEvent(ProfileEvents.FETCH_REQUEST, undefined);
        });

        it('should emit and receive fetch success event', (done) => {
            const mockProfile: ProfileData = {
                username: 'testuser',
                email: 'test@example.com',
                displayName: 'Test User',
                userId: 1,
                firstName: 'Test',
                lastName: 'User',
                age: 25,
                gender: 'prefer_not_to_say'
            };

            const payload = { profile: mockProfile };
            
            onProfileEvent(ProfileEvents.FETCH_SUCCESS, (data) => {
                expect(data).toEqual(payload);
                done();
            });

            emitProfileEvent(ProfileEvents.FETCH_SUCCESS, payload);
        });

        it('should emit and receive fetch error event', (done) => {
            const error = new Error('Network error');
            
            onProfileEvent(ProfileEvents.FETCH_ERROR, (data) => {
                expect(data.error).toBe(error);
                done();
            });

            emitProfileEvent(ProfileEvents.FETCH_ERROR, { error });
        });
    });

    describe('Event Listeners', () => {
        it('should handle multiple listeners for the same event', (done) => {
            const payload = { from: 'basic', to: 'physical' };
            let callCount = 0;

            const handler1 = () => {
                callCount++;
                if (callCount === 2) done();
            };

            const handler2 = () => {
                callCount++;
                if (callCount === 2) done();
            };

            onProfileEvent(ProfileEvents.SECTION_CHANGE, handler1);
            onProfileEvent(ProfileEvents.SECTION_CHANGE, handler2);

            emitProfileEvent(ProfileEvents.SECTION_CHANGE, payload);
        });

        it('should remove specific event listener', () => {
            const handler = jest.fn();
            
            onProfileEvent(ProfileEvents.FETCH_SUCCESS, handler);
            offProfileEvent(ProfileEvents.FETCH_SUCCESS, handler);

            emitProfileEvent(ProfileEvents.FETCH_SUCCESS, { 
                profile: {} as ProfileData 
            });
            
            expect(handler).not.toHaveBeenCalled();
        });

        it('should handle update success with fields tracking', (done) => {
            const mockProfile: ProfileData = {
                username: 'testuser',
                email: 'test@example.com',
                displayName: 'Test User',
                userId: 1,
                firstName: 'Test',
                lastName: 'User',
                age: 25,
                gender: 'prefer_not_to_say'
            };

            const payload = {
                profile: mockProfile,
                updatedFields: ['firstName', 'lastName']
            };

            onProfileEvent(ProfileEvents.UPDATE_SUCCESS, (data) => {
                expect(data.profile).toEqual(mockProfile);
                expect(data.updatedFields).toEqual(['firstName', 'lastName']);
                done();
            });

            emitProfileEvent(ProfileEvents.UPDATE_SUCCESS, payload);
        });
    });
}); 