import { AuthServiceError } from './errors';
import { AuthErrorCode } from '../types';

interface RateLimiterConfig {
    maxAttempts: number;
    windowMs: number;
    blockDurationMs: number;
}

interface RateLimitState {
    attempts: number;
    windowStart: number;
    blockedUntil: number | null;
}

const DEFAULT_CONFIG: RateLimiterConfig = {
    maxAttempts: 5,
    windowMs: 15 * 60 * 1000, // 15 minutes
    blockDurationMs: 60 * 60 * 1000, // 1 hour
};

export class RateLimiter {
    private static instance: RateLimiter;
    private readonly config: RateLimiterConfig;
    private readonly state: Map<string, RateLimitState>;

    private constructor(config: Partial<RateLimiterConfig> = {}) {
        this.config = { ...DEFAULT_CONFIG, ...config };
        this.state = new Map();
    }

    public static getInstance(config?: Partial<RateLimiterConfig>): RateLimiter {
        if (!RateLimiter.instance) {
            RateLimiter.instance = new RateLimiter(config);
        }
        return RateLimiter.instance;
    }

    private getState(key: string): RateLimitState {
        const now = Date.now();
        let state = this.state.get(key);

        if (!state || this.isWindowExpired(state)) {
            state = {
                attempts: 0,
                windowStart: now,
                blockedUntil: null,
            };
            this.state.set(key, state);
        }

        return state;
    }

    private isWindowExpired(state: RateLimitState): boolean {
        return Date.now() - state.windowStart >= this.config.windowMs;
    }

    public checkRateLimit(key: string): void {
        const state = this.getState(key);
        const now = Date.now();

        // Check if blocked
        if (state.blockedUntil !== null) {
            if (now < state.blockedUntil) {
                throw new AuthServiceError(
                    AuthErrorCode.RATE_LIMIT_EXCEEDED,
                    'Too many attempts. Please try again later.',
                    {
                        timeUntilUnblock: state.blockedUntil - now,
                    }
                );
            } else {
                // Block expired, reset state
                this.resetState(key);
                return;
            }
        }

        // Check attempts within window
        if (state.attempts >= this.config.maxAttempts) {
            // Block the key
            state.blockedUntil = now + this.config.blockDurationMs;
            throw new AuthServiceError(
                AuthErrorCode.RATE_LIMIT_EXCEEDED,
                'Too many attempts. Please try again later.',
                {
                    timeUntilUnblock: this.config.blockDurationMs,
                }
            );
        }
    }

    public incrementAttempts(key: string): void {
        const state = this.getState(key);
        state.attempts++;
    }

    public getRemainingAttempts(key: string): number {
        const state = this.getState(key);
        return Math.max(0, this.config.maxAttempts - state.attempts);
    }

    public getTimeUntilReset(key: string): number {
        const state = this.getState(key);
        const timeUntilReset = (state.windowStart + this.config.windowMs) - Date.now();
        return Math.max(0, timeUntilReset);
    }

    public getTimeUntilUnblock(key: string): number | null {
        const state = this.getState(key);
        if (!state.blockedUntil) {
            return null;
        }
        const timeUntilUnblock = state.blockedUntil - Date.now();
        return Math.max(0, timeUntilUnblock);
    }

    public isBlocked(key: string): boolean {
        const state = this.getState(key);
        return state.blockedUntil !== null && Date.now() < state.blockedUntil;
    }

    public resetState(key: string): void {
        const now = Date.now();
        this.state.set(key, {
            attempts: 0,
            windowStart: now,
            blockedUntil: null,
        });
    }
} 
} 