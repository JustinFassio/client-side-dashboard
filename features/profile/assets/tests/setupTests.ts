import '@testing-library/jest-dom';
import { configure } from '@testing-library/react';

// Configure testing library
configure({
    testIdAttribute: 'data-testid',
});

// Mock fetch API
global.fetch = jest.fn();

// Mock WordPress global variables and functions
global.wpApiSettings = {
    root: 'http://localhost/wp-json/',
    nonce: 'test-nonce'
};

// Reset mocks between tests
beforeEach(() => {
    jest.clearAllMocks();
    (global.fetch as jest.Mock).mockClear();
});

// Mock REST API responses
(global.fetch as jest.Mock).mockImplementation((url) => {
    if (url.includes('/profile')) {
        return Promise.resolve({
            ok: true,
            json: () => Promise.resolve({
                physical: {
                    height: 180,
                    weight: 75,
                    units: {
                        height: 'cm',
                        weight: 'kg'
                    }
                },
                experience: 'intermediate'
            })
        });
    }
    return Promise.reject(new Error('Not found'));
}); 