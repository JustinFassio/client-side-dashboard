require('@testing-library/jest-dom');

// Mock fetch globally
global.fetch = jest.fn();

// Mock WordPress dependencies
jest.mock('@wordpress/api-fetch', () => ({
    __esModule: true,
    default: jest.fn()
}));

jest.mock('@wordpress/element', () => ({
    ...jest.requireActual('@wordpress/element'),
    useCallback: jest.fn((fn) => fn),
    useState: jest.fn((initial) => [initial, jest.fn()]),
    useEffect: jest.fn(),
}));

// Mock crypto for UUID generation
global.crypto = {
    randomUUID: () => 'test-uuid'
};

// Extend expect matchers
expect.extend({
    toBeValidWorkoutPlan(received) {
        const pass = received &&
            typeof received === 'object' &&
            Array.isArray(received.exercises) &&
            typeof received.id === 'string' &&
            typeof received.name === 'string';

        return {
            message: () =>
                `expected ${received} to be a valid workout plan`,
            pass
        };
    }
});

// Clean up after each test
afterEach(() => {
    jest.clearAllMocks();
}); 