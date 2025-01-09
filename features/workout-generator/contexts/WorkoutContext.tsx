import React, { createContext, useContext, useReducer, ReactNode } from 'react';
import { WorkoutEvent } from '../events';

interface Workout {
    id: number;
    name: string;
    exercises: Array<{
        name: string;
        sets: number;
        reps: number;
        weight?: number;
    }>;
    createdAt: string;
    updatedAt: string;
}

interface WorkoutState {
    workouts: Workout[];
    loading: boolean;
    error: string | null;
    currentWorkout: Workout | null;
}

interface WorkoutContextType {
    state: WorkoutState;
    dispatch: React.Dispatch<WorkoutAction>;
}

type WorkoutAction = 
    | { type: WorkoutEvent.FETCH_REQUEST }
    | { type: WorkoutEvent.FETCH_SUCCESS; payload: Workout[] }
    | { type: WorkoutEvent.FETCH_ERROR; payload: string }
    | { type: WorkoutEvent.GENERATE_REQUEST }
    | { type: WorkoutEvent.GENERATE_SUCCESS; payload: Workout }
    | { type: WorkoutEvent.GENERATE_ERROR; payload: string };

const initialState: WorkoutState = {
    workouts: [],
    loading: false,
    error: null,
    currentWorkout: null
};

const WorkoutContext = createContext<WorkoutContextType | undefined>(undefined);

function workoutReducer(state: WorkoutState, action: WorkoutAction): WorkoutState {
    switch (action.type) {
        case WorkoutEvent.FETCH_REQUEST:
            return { ...state, loading: true, error: null };
        case WorkoutEvent.FETCH_SUCCESS:
            return { ...state, loading: false, workouts: action.payload };
        case WorkoutEvent.FETCH_ERROR:
            return { ...state, loading: false, error: action.payload };
        case WorkoutEvent.GENERATE_REQUEST:
            return { ...state, loading: true, error: null };
        case WorkoutEvent.GENERATE_SUCCESS:
            return {
                ...state,
                loading: false,
                currentWorkout: action.payload,
                workouts: [...state.workouts, action.payload]
            };
        case WorkoutEvent.GENERATE_ERROR:
            return { ...state, loading: false, error: action.payload };
        default:
            return state;
    }
}

export function WorkoutProvider({ children }: { children: ReactNode }) {
    const [state, dispatch] = useReducer(workoutReducer, initialState);

    return (
        <WorkoutContext.Provider value={{ state, dispatch }}>
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