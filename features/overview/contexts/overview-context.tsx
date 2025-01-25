import React, { createContext, useContext, useReducer, useCallback, ReactNode } from 'react';
import { overviewService, OverviewData } from '../services/overview-service';
import { ApiError } from '../../../dashboard/types/api';

interface OverviewState {
    data: OverviewData | null;
    loading: boolean;
    error: ApiError | null;
    lastUpdated: number | null;
}

interface OverviewContextValue {
    state: OverviewState;
    actions: {
        fetchOverview: (userId: number) => Promise<void>;
        refreshOverview: (userId: number) => Promise<void>;
        clearError: () => void;
    };
}

const initialState: OverviewState = {
    data: null,
    loading: false,
    error: null,
    lastUpdated: null
};

const OverviewContext = createContext<OverviewContextValue | undefined>(undefined);

type Action =
    | { type: 'FETCH_START' }
    | { type: 'FETCH_SUCCESS'; payload: OverviewData }
    | { type: 'FETCH_ERROR'; payload: ApiError }
    | { type: 'CLEAR_ERROR' };

function overviewReducer(state: OverviewState, action: Action): OverviewState {
    switch (action.type) {
        case 'FETCH_START':
            return {
                ...state,
                loading: true,
                error: null
            };
        case 'FETCH_SUCCESS':
            return {
                ...state,
                loading: false,
                data: action.payload,
                error: null,
                lastUpdated: Date.now()
            };
        case 'FETCH_ERROR':
            return {
                ...state,
                loading: false,
                error: action.payload
            };
        case 'CLEAR_ERROR':
            return {
                ...state,
                error: null
            };
        default:
            return state;
    }
}

export function OverviewProvider({ children }: { children: ReactNode }) {
    const [state, dispatch] = useReducer(overviewReducer, initialState);

    const fetchOverview = useCallback(async (userId: number) => {
        dispatch({ type: 'FETCH_START' });
        try {
            const response = await overviewService.getOverview(userId);
            if (response.error) {
                dispatch({ type: 'FETCH_ERROR', payload: response.error });
            } else if (response.data) {
                dispatch({ type: 'FETCH_SUCCESS', payload: response.data });
            }
        } catch (error) {
            const apiError: ApiError = {
                code: 'overview_error',
                message: error instanceof Error ? error.message : 'An unexpected error occurred',
                status: 500
            };
            dispatch({ type: 'FETCH_ERROR', payload: apiError });
        }
    }, []);

    const refreshOverview = useCallback(async (userId: number) => {
        const STALE_TIME = 5 * 60 * 1000; // 5 minutes
        if (!state.lastUpdated || Date.now() - state.lastUpdated > STALE_TIME) {
            await fetchOverview(userId);
        }
    }, [state.lastUpdated, fetchOverview]);

    const clearError = useCallback(() => {
        dispatch({ type: 'CLEAR_ERROR' });
    }, []);

    const value = {
        state,
        actions: {
            fetchOverview,
            refreshOverview,
            clearError
        }
    };

    return (
        <OverviewContext.Provider value={value}>
            {children}
        </OverviewContext.Provider>
    );
}

export function useOverview() {
    const context = useContext(OverviewContext);
    if (context === undefined) {
        throw new Error('useOverview must be used within an OverviewProvider');
    }
    return context;
} 