import React, { createContext, useContext } from 'react';
import { GeneratorSettings } from '../types/workout-types';

interface WorkoutContextValue {
    // Placeholder context value
    loading: boolean;
    error: string | null;
}

const WorkoutContext = createContext<WorkoutContextValue>({
    loading: false,
    error: null
});

export function WorkoutProvider({ children }: { children: React.ReactNode }) {
    return (
        <WorkoutContext.Provider value={{ loading: false, error: null }}>
            {children}
        </WorkoutContext.Provider>
    );
}

export function useWorkout() {
    return useContext(WorkoutContext);
} 