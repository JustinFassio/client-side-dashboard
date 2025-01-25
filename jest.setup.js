// jest-dom adds custom jest matchers for asserting on DOM nodes.
require('@testing-library/jest-dom');

// Add TextEncoder polyfill
const { TextEncoder, TextDecoder } = require('util');
global.TextEncoder = TextEncoder;
global.TextDecoder = TextDecoder;

// Mock window.matchMedia
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: jest.fn().mockImplementation(query => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: jest.fn(),
    removeListener: jest.fn(),
    addEventListener: jest.fn(),
    removeEventListener: jest.fn(),
    dispatchEvent: jest.fn(),
  })),
});

// Suppress React 18 warnings
const originalError = console.error;
console.error = (...args) => {
  if (/Warning: ReactDOM.render is no longer supported in React 18/.test(args[0])) {
    return;
  }
  originalError.call(console, ...args);
};

// Mock fetch globally
global.fetch = jest.fn(() => 
  Promise.resolve({
    ok: true,
    json: () => Promise.resolve({}),
  })
);

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