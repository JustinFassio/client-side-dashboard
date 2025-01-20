import { jest } from '@jest/globals';

declare global {
    var fetch: jest.Mock;
}

// Mock fetch globally
global.fetch = jest.fn();

// Reset all mocks before each test
beforeEach(() => {
    jest.resetAllMocks();
});

// Clean up after all tests
afterAll(() => {
    jest.restoreAllMocks();
}); 