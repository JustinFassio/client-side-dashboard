import { 
    PROFILE_EVENTS, 
    ProfileEvent, 
    ProfileEventUtils 
} from '../events';

describe('Profile Events', () => {
    describe('Event Types', () => {
        it('should define all required event types', () => {
            expect(PROFILE_EVENTS.FETCH_REQUEST).toBe('profile:fetch-request');
            expect(PROFILE_EVENTS.FETCH_SUCCESS).toBe('profile:fetch-success');
            expect(PROFILE_EVENTS.FETCH_ERROR).toBe('profile:fetch-error');
            expect(PROFILE_EVENTS.UPDATE_REQUEST).toBe('profile:update-request');
            expect(PROFILE_EVENTS.UPDATE_SUCCESS).toBe('profile:update-success');
            expect(PROFILE_EVENTS.UPDATE_ERROR).toBe('profile:update-error');
            expect(PROFILE_EVENTS.SECTION_CHANGE).toBe('profile:section-change');
            expect(PROFILE_EVENTS.VALIDATION_ERROR).toBe('profile:validation-error');
            expect(PROFILE_EVENTS.FORM_RESET).toBe('profile:form-reset');
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
                    fitnessLevel: 'intermediate',
                    activityLevel: 'moderately_active',
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
                        fitnessLevel: 'intermediate',
                        activityLevel: 'moderately_active',
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