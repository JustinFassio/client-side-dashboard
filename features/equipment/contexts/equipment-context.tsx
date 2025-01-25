import React, { createContext, useReducer, useContext, ReactNode, useCallback } from 'react';
import { Equipment, EquipmentSet, WorkoutZone } from '../types/equipment-types';
import { equipmentService } from '../services/equipment-service';
import { ApiError } from '../../../dashboard/types/api';

interface EquipmentState {
    equipment: Equipment[];
    equipmentSets: EquipmentSet[];
    workoutZones: WorkoutZone[];
    loading: boolean;
    error: ApiError | null;
}

type Action =
    | { type: 'SET_LOADING'; payload: boolean }
    | { type: 'SET_ERROR'; payload: ApiError | null }
    | { type: 'SET_EQUIPMENT'; payload: Equipment[] }
    | { type: 'SET_EQUIPMENT_SETS'; payload: EquipmentSet[] }
    | { type: 'SET_WORKOUT_ZONES'; payload: WorkoutZone[] }
    | { type: 'ADD_EQUIPMENT'; payload: Equipment }
    | { type: 'UPDATE_EQUIPMENT'; payload: Equipment }
    | { type: 'DELETE_EQUIPMENT'; payload: string };

interface EquipmentContextValue extends EquipmentState {
    actions: {
        fetchEquipment: () => Promise<void>;
        addEquipment: (equipment: Omit<Equipment, 'id'>) => Promise<void>;
        updateEquipment: (id: string, updates: Partial<Equipment>) => Promise<void>;
        deleteEquipment: (id: string) => Promise<void>;
        fetchEquipmentSets: () => Promise<void>;
        fetchWorkoutZones: () => Promise<void>;
        clearError: () => void;
    };
}

const EquipmentContext = createContext<EquipmentContextValue | undefined>(undefined);

const initialState: EquipmentState = {
    equipment: [],
    equipmentSets: [],
    workoutZones: [],
    loading: false,
    error: null
};

function equipmentReducer(state: EquipmentState, action: Action): EquipmentState {
    switch (action.type) {
        case 'SET_LOADING':
            return { ...state, loading: action.payload };
        case 'SET_ERROR':
            return { ...state, error: action.payload };
        case 'SET_EQUIPMENT':
            return { ...state, equipment: action.payload };
        case 'SET_EQUIPMENT_SETS':
            return { ...state, equipmentSets: action.payload };
        case 'SET_WORKOUT_ZONES':
            return { ...state, workoutZones: action.payload };
        case 'ADD_EQUIPMENT':
            return { ...state, equipment: [...state.equipment, action.payload] };
        case 'UPDATE_EQUIPMENT':
            return {
                ...state,
                equipment: state.equipment.map(item =>
                    item.id === action.payload.id ? action.payload : item
                )
            };
        case 'DELETE_EQUIPMENT':
            return {
                ...state,
                equipment: state.equipment.filter(item => item.id !== action.payload)
            };
        default:
            return state;
    }
}

export const EquipmentProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
    const [state, dispatch] = useReducer(equipmentReducer, initialState);

    const fetchEquipment = useCallback(async () => {
        dispatch({ type: 'SET_LOADING', payload: true });
        const response = await equipmentService.getEquipment();
        if (response.error) {
            dispatch({ type: 'SET_ERROR', payload: response.error });
        } else if (response.data) {
            dispatch({ type: 'SET_EQUIPMENT', payload: response.data });
        }
        dispatch({ type: 'SET_LOADING', payload: false });
    }, []);

    const addEquipment = useCallback(async (equipment: Omit<Equipment, 'id'>) => {
        dispatch({ type: 'SET_LOADING', payload: true });
        const response = await equipmentService.addEquipment(equipment);
        if (response.error) {
            dispatch({ type: 'SET_ERROR', payload: response.error });
        } else if (response.data) {
            dispatch({ type: 'ADD_EQUIPMENT', payload: response.data });
        }
        dispatch({ type: 'SET_LOADING', payload: false });
    }, []);

    const updateEquipment = useCallback(async (id: string, updates: Partial<Equipment>) => {
        dispatch({ type: 'SET_LOADING', payload: true });
        const response = await equipmentService.updateEquipment(id, updates);
        if (response.error) {
            dispatch({ type: 'SET_ERROR', payload: response.error });
        } else if (response.data) {
            dispatch({ type: 'UPDATE_EQUIPMENT', payload: response.data });
        }
        dispatch({ type: 'SET_LOADING', payload: false });
    }, []);

    const deleteEquipment = useCallback(async (id: string) => {
        dispatch({ type: 'SET_LOADING', payload: true });
        const response = await equipmentService.deleteEquipment(id);
        if (response.error) {
            dispatch({ type: 'SET_ERROR', payload: response.error });
        } else {
            dispatch({ type: 'DELETE_EQUIPMENT', payload: id });
        }
        dispatch({ type: 'SET_LOADING', payload: false });
    }, []);

    const fetchEquipmentSets = useCallback(async () => {
        dispatch({ type: 'SET_LOADING', payload: true });
        const response = await equipmentService.getEquipmentSets();
        if (response.error) {
            dispatch({ type: 'SET_ERROR', payload: response.error });
        } else if (response.data) {
            dispatch({ type: 'SET_EQUIPMENT_SETS', payload: response.data });
        }
        dispatch({ type: 'SET_LOADING', payload: false });
    }, []);

    const fetchWorkoutZones = useCallback(async () => {
        dispatch({ type: 'SET_LOADING', payload: true });
        const response = await equipmentService.getWorkoutZones();
        if (response.error) {
            dispatch({ type: 'SET_ERROR', payload: response.error });
        } else if (response.data) {
            dispatch({ type: 'SET_WORKOUT_ZONES', payload: response.data });
        }
        dispatch({ type: 'SET_LOADING', payload: false });
    }, []);

    const clearError = useCallback(() => {
        dispatch({ type: 'SET_ERROR', payload: null });
    }, []);

    const value: EquipmentContextValue = {
        ...state,
        actions: {
            fetchEquipment,
            addEquipment,
            updateEquipment,
            deleteEquipment,
            fetchEquipmentSets,
            fetchWorkoutZones,
            clearError
        }
    };

    return (
        <EquipmentContext.Provider value={value}>
            {children}
        </EquipmentContext.Provider>
    );
};

export const useEquipment = () => {
    const context = useContext(EquipmentContext);
    if (!context) {
        throw new Error('useEquipment must be used within an EquipmentProvider');
    }
    return context;
}; 