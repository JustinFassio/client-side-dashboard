import '@testing-library/jest-dom';
import { configure } from '@testing-library/dom';
import { TextEncoder, TextDecoder } from 'util';
import { Response, Request, Headers } from 'cross-fetch';

// Configure testing library
configure({
    testIdAttribute: 'data-testid',
});

// Configure for act() support
global.IS_REACT_ACT_ENVIRONMENT = true;

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

global.TextEncoder = TextEncoder;
global.TextDecoder = TextDecoder as any;
global.Response = Response as any;
global.Request = Request as any;
global.Headers = Headers as any;

class MockBroadcastChannel implements BroadcastChannel {
    readonly name: string;
    onmessage: ((this: BroadcastChannel, ev: MessageEvent) => any) | null = null;
    onmessageerror: ((this: BroadcastChannel, ev: MessageEvent) => any) | null = null;

    constructor(name: string) {
        this.name = name;
    }

    postMessage(message: any): void {}
    
    addEventListener<K extends keyof BroadcastChannelEventMap>(
        type: K,
        listener: (this: BroadcastChannel, ev: BroadcastChannelEventMap[K]) => any,
        options?: boolean | AddEventListenerOptions
    ): void;
    addEventListener(
        type: string,
        listener: EventListenerOrEventListenerObject,
        options?: boolean | AddEventListenerOptions
    ): void {
        // Implementation not needed for mock
    }

    removeEventListener<K extends keyof BroadcastChannelEventMap>(
        type: K,
        listener: (this: BroadcastChannel, ev: BroadcastChannelEventMap[K]) => any,
        options?: boolean | EventListenerOptions
    ): void;
    removeEventListener(
        type: string,
        listener: EventListenerOrEventListenerObject,
        options?: boolean | EventListenerOptions
    ): void {
        // Implementation not needed for mock
    }

    close(): void {}

    dispatchEvent(event: Event): boolean {
        return true;
    }
}

global.BroadcastChannel = MockBroadcastChannel as any; 