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

// Clean up after each test
afterEach(() => {
    jest.clearAllMocks();
}); 