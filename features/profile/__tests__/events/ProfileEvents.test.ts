import { Events } from '../../../../dashboard/core/events';
import { ProfileEvent } from '../../events/types';
import { ProfileData } from '../../types/profile';
import { ApiError } from '../../../../dashboard/types/api';

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
                username: 'testuser',
                email: 'test@example.com',
                displayName: 'Test User',
                firstName: 'Test',
                lastName: 'User',
                nickname: 'tester',
                roles: ['subscriber'],
                phone: '',
                age: 30,
                dateOfBirth: '1993-01-01',
                gender: 'male',
                heightCm: 175,
                weightKg: 70,
                experienceLevel: 'intermediate',
                medicalClearance: true,
                medicalNotes: '',
                medicalConditions: [],
                exerciseLimitations: [],
                medications: '',
                emergencyContactName: '',
                emergencyContactPhone: '',
                injuries: [],
                equipment: [],
                fitnessGoals: ['strength', 'endurance'],
                dominantSide: 'right'
            };

            Events.on(ProfileEvent.FETCH_SUCCESS, listener);
            Events.emit(ProfileEvent.FETCH_SUCCESS, { profile: mockProfile });

            expect(listener).toHaveBeenCalledWith({ profile: mockProfile });
        });

        it('emits fetch error event', () => {
            const listener = jest.fn();
            const error: ApiError = {
                code: 'NETWORK_ERROR',
                message: 'Fetch failed',
                status: 500
            };

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
                nickname: 'jane',
                roles: ['subscriber'],
                phone: '',
                age: 28,
                dateOfBirth: '1995-01-01',
                gender: 'female',
                heightCm: 165,
                weightKg: 60,
                experienceLevel: 'intermediate',
                medicalClearance: true,
                medicalNotes: '',
                medicalConditions: [],
                exerciseLimitations: [],
                medications: '',
                emergencyContactName: '',
                emergencyContactPhone: '',
                injuries: [],
                equipment: [],
                fitnessGoals: ['strength', 'endurance'],
                dominantSide: 'right'
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

    describe('error handling', () => {
        it('emits update error event with attempted data', () => {
            const listener = jest.fn();
            const error: ApiError = {
                code: 'NETWORK_ERROR',
                message: 'Update failed',
                status: 500
            };
            const attemptedData = { firstName: 'Jane' };

            Events.on(ProfileEvent.UPDATE_ERROR, listener);
            Events.emit(ProfileEvent.UPDATE_ERROR, { 
                error,
                attemptedData 
            });

            expect(listener).toHaveBeenCalledWith({ 
                error,
                attemptedData 
            });
        });

        it('emits validation error event', () => {
            const listener = jest.fn();
            const errors = {
                firstName: ['First name is required'],
                email: ['Invalid email format']
            };

            Events.on(ProfileEvent.VALIDATION_ERROR, listener);
            Events.emit(ProfileEvent.VALIDATION_ERROR, { errors });

            expect(listener).toHaveBeenCalledWith({ errors });
        });

        it('handles network errors during profile update', () => {
            const listener = jest.fn();
            const networkError: ApiError = {
                code: 'NETWORK_ERROR',
                message: 'Network request failed',
                status: 500
            };
            
            Events.on(ProfileEvent.UPDATE_ERROR, listener);
            Events.emit(ProfileEvent.UPDATE_ERROR, { 
                error: networkError,
                attemptedData: { firstName: 'John' }
            });

            expect(listener).toHaveBeenCalledWith({
                error: networkError,
                attemptedData: { firstName: 'John' }
            });
        });

        it('handles invalid response data errors', () => {
            const listener = jest.fn();
            const invalidDataError: ApiError = {
                code: 'INVALID_RESPONSE',
                message: 'Invalid profile data structure',
                status: 400
            };
            
            Events.on(ProfileEvent.UPDATE_ERROR, listener);
            Events.emit(ProfileEvent.UPDATE_ERROR, { 
                error: invalidDataError
            });

            expect(listener).toHaveBeenCalledWith({
                error: invalidDataError
            });
        });
    });

    describe('form reset', () => {
        it('emits form reset event', () => {
            const listener = jest.fn();
            
            Events.on(ProfileEvent.FORM_RESET, listener);
            Events.emit(ProfileEvent.FORM_RESET);

            expect(listener).toHaveBeenCalled();
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