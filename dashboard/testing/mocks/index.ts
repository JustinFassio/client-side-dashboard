import { FeatureContext } from '../../contracts/Feature';
import { DashboardEvents } from '../../core/events';

export const mockFeatureContext = (): FeatureContext => ({
    userId: 1,
    nonce: 'test-nonce',
    apiUrl: 'http://test.local/wp-json',
    debug: false,
    navigate: jest.fn(),
    isEnabled: jest.fn(() => true),
    dispatch: jest.fn(() => jest.fn()),
    addListener: jest.fn(),
    unsubscribe: jest.fn()
});

export const mockDashboardEvents = (): DashboardEvents => ({
    emit: jest.fn(),
    on: jest.fn(),
    off: jest.fn(),
    removeAllListeners: jest.fn(),
    removeListener: jest.fn(),
    addListener: jest.fn(),
    once: jest.fn(),
    eventNames: jest.fn(),
    getMaxListeners: jest.fn(),
    listenerCount: jest.fn(),
    listeners: jest.fn(),
    prependListener: jest.fn(),
    prependOnceListener: jest.fn(),
    rawListeners: jest.fn(),
    setMaxListeners: jest.fn()
}); 