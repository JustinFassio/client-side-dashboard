import { Events } from '../../../dashboard/core/events';
import { ProfileEvents } from '../events/types';
import { 
    LEGACY_PROFILE_EVENTS, 
    emitCompatibleEvent, 
    onCompatibleEvent 
} from '../events/compatibility';

describe('Profile Events Compatibility Layer', () => {
    beforeEach(() => {
        jest.spyOn(console, 'log').mockImplementation(() => {});
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    describe('Legacy Event Support', () => {
        it('should emit legacy events', (done) => {
            const mockProfile = { username: 'test' };
            
            Events.on(LEGACY_PROFILE_EVENTS.PROFILE_UPDATED, (data) => {
                expect(data).toEqual({ profile: mockProfile });
                done();
            });

            emitCompatibleEvent(LEGACY_PROFILE_EVENTS.PROFILE_UPDATED, { profile: mockProfile });
        });

        it('should handle legacy event listeners', (done) => {
            const mockProfile = { username: 'test' };
            
            onCompatibleEvent(LEGACY_PROFILE_EVENTS.PROFILE_UPDATED, (data) => {
                expect(data).toEqual({ profile: mockProfile });
                done();
            });

            Events.emit(LEGACY_PROFILE_EVENTS.PROFILE_UPDATED, { profile: mockProfile });
        });
    });

    describe('New Event Support', () => {
        it('should emit new events when using compatibility layer', (done) => {
            const mockProfile = { username: 'test' };
            
            Events.on(ProfileEvents.UPDATE_SUCCESS, (data) => {
                expect(data.profile).toEqual(mockProfile);
                expect(Array.isArray(data.updatedFields)).toBe(true);
                done();
            });

            emitCompatibleEvent(LEGACY_PROFILE_EVENTS.PROFILE_UPDATED, { profile: mockProfile });
        });

        it('should transform payloads correctly', (done) => {
            const mockError = new Error('Update failed');
            
            Events.on(ProfileEvents.UPDATE_ERROR, (data) => {
                expect(data.error).toBe(mockError);
                expect(data.attemptedData).toBeUndefined();
                done();
            });

            emitCompatibleEvent(LEGACY_PROFILE_EVENTS.PROFILE_UPDATE_FAILED, { error: mockError });
        });
    });

    describe('Bidirectional Compatibility', () => {
        it('should handle both legacy and new events simultaneously', (done) => {
            const mockProfile = { username: 'test' };
            let callCount = 0;

            // Listen with legacy method
            onCompatibleEvent(LEGACY_PROFILE_EVENTS.PROFILE_UPDATED, () => {
                callCount++;
                if (callCount === 2) done();
            });

            // Listen with new method
            Events.on(ProfileEvents.UPDATE_SUCCESS, () => {
                callCount++;
                if (callCount === 2) done();
            });

            // Emit with compatibility layer
            emitCompatibleEvent(LEGACY_PROFILE_EVENTS.PROFILE_UPDATED, { profile: mockProfile });
        });
    });
}); 