import { createReduxStore, register } from '@wordpress/data';

interface DashboardState {
    profile: any | null;
    isLoading: boolean;
    error: string | null;
}

const DEFAULT_STATE: DashboardState = {
    profile: null,
    isLoading: false,
    error: null
};

const STORE_NAME = 'athlete-dashboard';

// Action Types
const ACTION_TYPES = {
    FETCH_PROFILE: 'profile:fetch-request',
    SET_PROFILE: 'profile:set',
    SET_LOADING: 'profile:loading',
    SET_ERROR: 'profile:error'
} as const;

// Action Creators
const actions = {
    fetchProfile: (userId: string) => ({
        type: ACTION_TYPES.FETCH_PROFILE,
        payload: { userId }
    }),
    setProfile: (profile: any) => ({
        type: ACTION_TYPES.SET_PROFILE,
        payload: profile
    }),
    setLoading: (isLoading: boolean) => ({
        type: ACTION_TYPES.SET_LOADING,
        payload: isLoading
    }),
    setError: (error: string | null) => ({
        type: ACTION_TYPES.SET_ERROR,
        payload: error
    })
};

// Store Configuration
const storeConfig = {
    reducer(state = DEFAULT_STATE, action: any): DashboardState {
        switch (action.type) {
            case ACTION_TYPES.FETCH_PROFILE:
                return {
                    ...state,
                    isLoading: true,
                    error: null
                };
            case ACTION_TYPES.SET_PROFILE:
                return {
                    ...state,
                    profile: action.payload,
                    isLoading: false,
                    error: null
                };
            case ACTION_TYPES.SET_LOADING:
                return {
                    ...state,
                    isLoading: action.payload
                };
            case ACTION_TYPES.SET_ERROR:
                return {
                    ...state,
                    error: action.payload,
                    isLoading: false
                };
            default:
                return state;
        }
    },

    actions,

    selectors: {
        getProfile(state: DashboardState) {
            return state.profile;
        },
        isLoading(state: DashboardState) {
            return state.isLoading;
        },
        getError(state: DashboardState) {
            return state.error;
        }
    },

    controls: {},
    resolvers: {}
};

// Create and register store
const store = createReduxStore(STORE_NAME, storeConfig);

export const initializeStore = () => {
    if (!window.wp?.data?.stores?.[STORE_NAME]) {
        console.log(`Registering ${STORE_NAME} store with config:`, storeConfig);
        register(store);
        
        // Get the registered store's dispatch
        const registeredDispatch = window.wp.data.dispatch(STORE_NAME);
        
        if (registeredDispatch) {
            // Add action creators as methods
            Object.keys(actions).forEach(actionName => {
                const actionCreator = actions[actionName as keyof typeof actions];
                if (typeof actionCreator === 'function') {
                    // Create a direct dispatch method that doesn't recurse
                    registeredDispatch[actionName] = (payload: any) => {
                        console.log(`Dispatching ${actionName} with payload:`, payload);
                        const action = actionCreator(payload);
                        console.log('Created action:', action);
                        
                        // Handle different store types
                        if (typeof registeredDispatch === 'function') {
                            return registeredDispatch(action);
                        } else if (typeof registeredDispatch.dispatch === 'function') {
                            return registeredDispatch.dispatch(action);
                        } else {
                            const methodName = actionName.toLowerCase();
                            if (typeof registeredDispatch[methodName] === 'function') {
                                return registeredDispatch[methodName](payload);
                            }
                            console.error('No valid dispatch method found for action:', action);
                            return null;
                        }
                    };
                }
            });
            
            // Add a generic dispatch method if needed
            if (!registeredDispatch.dispatch) {
                registeredDispatch.dispatch = (action: any) => {
                    console.log('Using generic dispatch for action:', action);
                    return typeof registeredDispatch === 'function' 
                        ? registeredDispatch(action)
                        : null;
                };
            }
        }
        
        console.log('Store registration check:', {
            store: !!window.wp.data.select(STORE_NAME),
            dispatch: !!registeredDispatch,
            actions: Object.keys(actions),
            registeredActions: registeredDispatch ? Object.keys(registeredDispatch) : []
        });
    } else {
        console.warn(`Store ${STORE_NAME} is already registered`);
    }
}; 