import { Events } from '../../../../dashboard/core/events';
import { ProfileEvent } from '../../events';
import { ProfileData } from '../../types/profile';

describe('Profile Events', () => {
    beforeEach(() => {
        Events.removeAllListeners();
    });

    describe('fetch events', () => {
        it('emits fetch request event', () => {
            const listener = jest.fn();
            Events.on(ProfileEvent.FETCH_REQUEST, listener);

            Events.emit(ProfileEvent.FETCH_REQUEST, { userId: 1 });
            expect(listener).toHaveBeenCalledWith({ userId: 1 });
        });

        it('emits fetch success event with profile data', () => {
            const listener = jest.fn();
            const mockProfile: ProfileData = {
                id: 1,
                username: 'johndoe',
                email: 'john@example.com',
                displayName: 'John Doe',
                firstName: 'John',
                lastName: 'Doe',
                age: 30,
                gender: 'male',
                height: 180,
                weight: 80,
                fitnessLevel: 'intermediate',
                activityLevel: 'moderately_active',
                medicalConditions: [],
                exerciseLimitations: [],
                medications: '',
                physicalMetrics: [
                    {
                        type: 'height',
                        value: 180,
                        unit: 'cm',
                        date: '2023-01-01'
                    },
                    {
                        type: 'weight',
                        value: 80,
                        unit: 'kg',
                        date: '2023-01-01'
                    }
                ]
            };

            Events.on(ProfileEvent.FETCH_SUCCESS, listener);
            Events.emit(ProfileEvent.FETCH_SUCCESS, { profile: mockProfile });

            expect(listener).toHaveBeenCalledWith({ profile: mockProfile });
        });

        it('emits fetch error event', () => {
            const listener = jest.fn();
            const error = new Error('Fetch failed');

            Events.on(ProfileEvent.FETCH_ERROR, listener);
            Events.emit(ProfileEvent.FETCH_ERROR, { error });

            expect(listener).toHaveBeenCalledWith({ error });
        });
    });

    describe('update events', () => {
        it('emits update request event', () => {
            const listener = jest.fn();
            const updateData = { firstName: 'Jane' };

            Events.on(ProfileEvent.UPDATE_REQUEST, listener);
            Events.emit(ProfileEvent.UPDATE_REQUEST, { 
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
            const mockProfile: ProfileData = {
                id: 1,
                username: 'janedoe',
                email: 'jane@example.com',
                displayName: 'Jane Doe',
                firstName: 'Jane',
                lastName: 'Doe',
                age: 28,
                gender: 'female',
                height: 165,
                weight: 60,
                fitnessLevel: 'intermediate',
                activityLevel: 'moderately_active',
                medicalConditions: [],
                exerciseLimitations: [],
                medications: '',
                physicalMetrics: [
                    {
                        type: 'height',
                        value: 165,
                        unit: 'cm',
                        date: '2023-01-01'
                    },
                    {
                        type: 'weight',
                        value: 60,
                        unit: 'kg',
                        date: '2023-01-01'
                    }
                ]
            };

            Events.on(ProfileEvent.UPDATE_SUCCESS, listener);
            Events.emit(ProfileEvent.UPDATE_SUCCESS, { 
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
            Events.on(ProfileEvent.FETCH_REQUEST, listener);
            Events.removeListener(ProfileEvent.FETCH_REQUEST, listener);

            Events.emit(ProfileEvent.FETCH_REQUEST, { userId: 1 });
            expect(listener).not.toHaveBeenCalled();
        });
    });
}); 