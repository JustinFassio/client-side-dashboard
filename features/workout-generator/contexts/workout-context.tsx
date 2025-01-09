import React, { createContext, useContext, useReducer, useCallback, ReactNode } from 'react';
import { workoutService } from '../services/workout-service';
import { WorkoutPlan, WorkoutPreferences, GeneratorSettings, WorkoutStatus } from '../types/workout-types';
import { ApiError } from '../../../dashboard/types/api';

interface WorkoutState {
    currentWorkout: WorkoutPlan | null;
    workoutHistory: WorkoutPlan[];
    preferences: WorkoutPreferences | null;
    settings: GeneratorSettings | null;
    status: WorkoutStatus;
    loading: boolean;
    error: ApiError | null;
}

interface WorkoutContextValue {
    state: WorkoutState;
    actions: {
        generateWorkout: (preferences: WorkoutPreferences, settings: GeneratorSettings) => Promise<void>;
        saveWorkout: (workout: WorkoutPlan) => Promise<void>;
        loadHistory: () => Promise<void>;
        updatePreferences: (preferences: WorkoutPreferences) => void;
        updateSettings: (settings: GeneratorSettings) => void;
        clearError: () => void;
    };
}

const initialState: WorkoutState = {
    currentWorkout: null,
    workoutHistory: [],
    preferences: null,
    settings: null,
    status: 'pending',
    loading: false,
    error: null
};

const WorkoutContext = createContext<WorkoutContextValue | undefined>(undefined);

type Action =
    | { type: 'SET_WORKOUT'; payload: WorkoutPlan }
    | { type: 'SET_HISTORY'; payload: WorkoutPlan[] }
    | { type: 'SET_PREFERENCES'; payload: WorkoutPreferences }
    | { type: 'SET_SETTINGS'; payload: GeneratorSettings }
    | { type: 'SET_STATUS'; payload: WorkoutStatus }
    | { type: 'SET_LOADING'; payload: boolean }
    | { type: 'SET_ERROR'; payload: ApiError | null };

function workoutReducer(state: WorkoutState, action: Action): WorkoutState {
    switch (action.type) {
        case 'SET_WORKOUT':
            return { ...state, currentWorkout: action.payload };
        case 'SET_HISTORY':
            return { ...state, workoutHistory: action.payload };
        case 'SET_PREFERENCES':
            return { ...state, preferences: action.payload };
        case 'SET_SETTINGS':
            return { ...state, settings: action.payload };
        case 'SET_STATUS':
            return { ...state, status: action.payload };
        case 'SET_LOADING':
            return { ...state, loading: action.payload };
        case 'SET_ERROR':
            return { ...state, error: action.payload };
        default:
            return state;
    }
}

export function WorkoutProvider({ children }: { children: ReactNode }) {
    const [state, dispatch] = useReducer(workoutReducer, initialState);

    const generateWorkout = useCallback(async (
        preferences: WorkoutPreferences,
        settings: GeneratorSettings
    ) => {
        dispatch({ type: 'SET_LOADING', payload: true });
        dispatch({ type: 'SET_STATUS', payload: 'generating' });
        try {
            const response = await workoutService.generateWorkout(1, preferences, settings);
            if (response.error) {
                dispatch({ type: 'SET_ERROR', payload: response.error });
                dispatch({ type: 'SET_STATUS', payload: 'failed' });
            } else if (response.data) {
                dispatch({ type: 'SET_WORKOUT', payload: response.data });
                dispatch({ type: 'SET_STATUS', payload: 'completed' });
            }
        } catch (error) {
            dispatch({
                type: 'SET_ERROR',
                payload: {
                    code: 'generation_error',
                    message: error instanceof Error ? error.message : 'Failed to generate workout',
                    status: 500
                }
            });
            dispatch({ type: 'SET_STATUS', payload: 'failed' });
        } finally {
            dispatch({ type: 'SET_LOADING', payload: false });
        }
    }, []);

    const saveWorkout = useCallback(async (workout: WorkoutPlan) => {
        dispatch({ type: 'SET_LOADING', payload: true });
        try {
            const response = await workoutService.saveWorkout(1, workout);
            if (response.error) {
                dispatch({ type: 'SET_ERROR', payload: response.error });
            } else if (response.data) {
                dispatch({ type: 'SET_WORKOUT', payload: response.data });
            }
        } catch (error) {
            dispatch({
                type: 'SET_ERROR',
                payload: {
                    code: 'save_error',
                    message: error instanceof Error ? error.message : 'Failed to save workout',
                    status: 500
                }
            });
        } finally {
            dispatch({ type: 'SET_LOADING', payload: false });
        }
    }, []);

    const loadHistory = useCallback(async () => {
        dispatch({ type: 'SET_LOADING', payload: true });
        try {
            const response = await workoutService.getWorkoutHistory(1);
            if (response.error) {
                dispatch({ type: 'SET_ERROR', payload: response.error });
            } else if (response.data) {
                dispatch({ type: 'SET_HISTORY', payload: response.data });
            }
        } catch (error) {
            dispatch({
                type: 'SET_ERROR',
                payload: {
                    code: 'history_error',
                    message: error instanceof Error ? error.message : 'Failed to load workout history',
                    status: 500
                }
            });
        } finally {
            dispatch({ type: 'SET_LOADING', payload: false });
        }
    }, []);

    const updatePreferences = useCallback((preferences: WorkoutPreferences) => {
        dispatch({ type: 'SET_PREFERENCES', payload: preferences });
    }, []);

    const updateSettings = useCallback((settings: GeneratorSettings) => {
        dispatch({ type: 'SET_SETTINGS', payload: settings });
    }, []);

    const clearError = useCallback(() => {
        dispatch({ type: 'SET_ERROR', payload: null });
    }, []);

    const value = {
        state,
        actions: {
            generateWorkout,
            saveWorkout,
            loadHistory,
            updatePreferences,
            updateSettings,
            clearError
        }
    };

    return (
        <WorkoutContext.Provider value={value}>
            {children}
        </WorkoutContext.Provider>
    );
}

export function useWorkout() {
    const context = useContext(WorkoutContext);
    if (context === undefined) {
        throw new Error('useWorkout must be used within a WorkoutProvider');
    }
    return context;
} 