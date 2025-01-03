import { 
    PROFILE_EVENTS, 
    ProfileEvent, 
    ProfileEventUtils 
} from '../events';

describe('Profile Events', () => {
    describe('Event Types', () => {
        it('should define all required event types', () => {
            expect(PROFILE_EVENTS.FETCH_REQUEST).toBe('profile_fetch_request');
            expect(PROFILE_EVENTS.FETCH_SUCCESS).toBe('profile_fetch_success');
            expect(PROFILE_EVENTS.FETCH_ERROR).toBe('profile_fetch_error');
            expect(PROFILE_EVENTS.UPDATE_REQUEST).toBe('profile_update_request');
            expect(PROFILE_EVENTS.UPDATE_SUCCESS).toBe('profile_update_success');
            expect(PROFILE_EVENTS.UPDATE_ERROR).toBe('profile_update_error');
            expect(PROFILE_EVENTS.SECTION_CHANGE).toBe('profile_section_change');
            expect(PROFILE_EVENTS.VALIDATION_ERROR).toBe('profile_validation_error');
            expect(PROFILE_EVENTS.FORM_RESET).toBe('profile_form_reset');
        });
    });

    describe('ProfileEventUtils', () => {
        describe('createEvent', () => {
            it('should create an event without payload', () => {
                const event = ProfileEventUtils.createEvent(PROFILE_EVENTS.FETCH_REQUEST);
                expect(event).toEqual({ type: PROFILE_EVENTS.FETCH_REQUEST });
            });

            it('should create an event with payload', () => {
                const mockProfile = { 
                    username: 'test',
                    email: 'test@example.com',
                    displayName: 'Test User',
                    firstName: 'Test',
                    lastName: 'User',
                    age: 25,
                    gender: 'prefer_not_to_say',
                    height: 175,
                    weight: 70,
                    fitnessLevel: 'intermediate' as const,
                    activityLevel: 'moderately_active' as const,
                    medicalConditions: [],
                    exerciseLimitations: [],
                    medications: ''
                };

                const event = ProfileEventUtils.createEvent(
                    PROFILE_EVENTS.FETCH_SUCCESS,
                    mockProfile
                );

                expect(event).toEqual({
                    type: PROFILE_EVENTS.FETCH_SUCCESS,
                    payload: mockProfile
                });
            });
        });

        describe('isEventType', () => {
            it('should correctly identify event types', () => {
                const fetchRequest: ProfileEvent = { 
                    type: PROFILE_EVENTS.FETCH_REQUEST 
                };
                const fetchSuccess: ProfileEvent = { 
                    type: PROFILE_EVENTS.FETCH_SUCCESS,
                    payload: {} as any
                };

                expect(ProfileEventUtils.isEventType(fetchRequest, PROFILE_EVENTS.FETCH_REQUEST)).toBe(true);
                expect(ProfileEventUtils.isEventType(fetchRequest, PROFILE_EVENTS.FETCH_SUCCESS)).toBe(false);
                expect(ProfileEventUtils.isEventType(fetchSuccess, PROFILE_EVENTS.FETCH_SUCCESS)).toBe(true);
            });
        });

        describe('hasPayload', () => {
            it('should correctly identify events with payloads', () => {
                const withPayload: ProfileEvent = {
                    type: PROFILE_EVENTS.UPDATE_SUCCESS,
                    payload: {} as any
                };
                const withoutPayload: ProfileEvent = {
                    type: PROFILE_EVENTS.FORM_RESET
                };

                expect(ProfileEventUtils.hasPayload(withPayload)).toBe(true);
                expect(ProfileEventUtils.hasPayload(withoutPayload)).toBe(false);
            });
        });
    });

    describe('Type Safety', () => {
        it('should enforce correct payload types', () => {
            // These should compile without type errors
            const validEvents: ProfileEvent[] = [
                { 
                    type: PROFILE_EVENTS.FETCH_REQUEST 
                },
                { 
                    type: PROFILE_EVENTS.FETCH_SUCCESS,
                    payload: {
                        username: 'test',
                        email: 'test@example.com',
                        displayName: 'Test User',
                        firstName: 'Test',
                        lastName: 'User',
                        age: 25,
                        gender: 'prefer_not_to_say',
                        height: 175,
                        weight: 70,
                        fitnessLevel: 'intermediate' as const,
                        activityLevel: 'moderately_active' as const,
                        medicalConditions: [],
                        exerciseLimitations: [],
                        medications: ''
                    }
                },
                { 
                    type: PROFILE_EVENTS.FETCH_ERROR,
                    error: { 
                        code: 'VALIDATION_ERROR',
                        message: 'Invalid data'
                    }
                },
                {
                    type: PROFILE_EVENTS.SECTION_CHANGE,
                    section: 'basic'
                },
                {
                    type: PROFILE_EVENTS.VALIDATION_ERROR,
                    errors: {
                        email: ['Invalid email format']
                    }
                },
                {
                    type: PROFILE_EVENTS.FORM_RESET
                }
            ];

            // Just checking that the array is defined
            expect(validEvents).toBeDefined();
            expect(validEvents.length).toBe(6);
        });
    });
}); 