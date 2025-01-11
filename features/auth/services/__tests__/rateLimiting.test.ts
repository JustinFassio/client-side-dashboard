import { RateLimiter } from '../rateLimiting';

describe('RateLimiter', () => {
    let rateLimiter: RateLimiter;
    const testKey = 'test:user@example.com';

    beforeEach(() => {
        // Reset the singleton instance before each test
        (RateLimiter as any).instance = undefined;
        rateLimiter = RateLimiter.getInstance({
            maxAttempts: 3,
            windowMs: 1000, // 1 second for faster testing
            blockDurationMs: 2000, // 2 seconds for faster testing
        });
    });

    describe('getInstance', () => {
        it('should create a singleton instance', () => {
            const instance1 = RateLimiter.getInstance();
            const instance2 = RateLimiter.getInstance();
            expect(instance1).toBe(instance2);
        });

        it('should use default config if none provided', () => {
            const instance = RateLimiter.getInstance();
            expect(instance).toBeDefined();
        });
    });

    describe('checkRateLimit', () => {
        it('should allow requests within limit', () => {
            expect(() => rateLimiter.checkRateLimit(testKey)).not.toThrow();
            expect(() => rateLimiter.checkRateLimit(testKey)).not.toThrow();
            expect(() => rateLimiter.checkRateLimit(testKey)).not.toThrow();
        });

        it('should block requests over limit', () => {
            rateLimiter.checkRateLimit(testKey);
            rateLimiter.incrementAttempts(testKey);
            rateLimiter.checkRateLimit(testKey);
            rateLimiter.incrementAttempts(testKey);
            rateLimiter.checkRateLimit(testKey);
            rateLimiter.incrementAttempts(testKey);

            expect(() => rateLimiter.checkRateLimit(testKey)).toThrow(AuthServiceError);
            expect(() => rateLimiter.checkRateLimit(testKey)).toThrow(
                /Too many attempts/
            );
        });

        it('should reset after window expires', async () => {
            rateLimiter.checkRateLimit(testKey);
            rateLimiter.incrementAttempts(testKey);
            rateLimiter.checkRateLimit(testKey);
            rateLimiter.incrementAttempts(testKey);
            rateLimiter.checkRateLimit(testKey);
            rateLimiter.incrementAttempts(testKey);

            // Wait for window to expire
            await new Promise(resolve => setTimeout(resolve, 1100));

            // Should be able to make requests again
            expect(() => rateLimiter.checkRateLimit(testKey)).not.toThrow();
        });

        it('should maintain block for full duration', async () => {
            // Exceed limit to trigger block
            for (let i = 0; i < 3; i++) {
                rateLimiter.checkRateLimit(testKey);
                rateLimiter.incrementAttempts(testKey);
            }

            // Verify blocked
            expect(() => rateLimiter.checkRateLimit(testKey)).toThrow(AuthServiceError);

            // Wait for window but not block duration
            await new Promise(resolve => setTimeout(resolve, 1100));

            // Should still be blocked
            expect(() => rateLimiter.checkRateLimit(testKey)).toThrow(AuthServiceError);

            // Wait for full block duration
            await new Promise(resolve => setTimeout(resolve, 1000));

            // Should be unblocked
            expect(() => rateLimiter.checkRateLimit(testKey)).not.toThrow();
        });
    });

    describe('getRemainingAttempts', () => {
        it('should return correct remaining attempts', () => {
            expect(rateLimiter.getRemainingAttempts(testKey)).toBe(3);

            rateLimiter.incrementAttempts(testKey);
            expect(rateLimiter.getRemainingAttempts(testKey)).toBe(2);

            rateLimiter.incrementAttempts(testKey);
            expect(rateLimiter.getRemainingAttempts(testKey)).toBe(1);

            rateLimiter.incrementAttempts(testKey);
            expect(rateLimiter.getRemainingAttempts(testKey)).toBe(0);
        });

        it('should reset remaining attempts after window expires', async () => {
            rateLimiter.incrementAttempts(testKey);
            rateLimiter.incrementAttempts(testKey);
            expect(rateLimiter.getRemainingAttempts(testKey)).toBe(1);

            await new Promise(resolve => setTimeout(resolve, 1100));

            expect(rateLimiter.getRemainingAttempts(testKey)).toBe(3);
        });
    });

    describe('getTimeUntilReset', () => {
        it('should return correct time until window reset', async () => {
            rateLimiter.incrementAttempts(testKey);
            const timeUntilReset = rateLimiter.getTimeUntilReset(testKey);
            expect(timeUntilReset).toBeGreaterThan(0);
            expect(timeUntilReset).toBeLessThanOrEqual(1000);
        });

        it('should return 0 after window expires', async () => {
            rateLimiter.incrementAttempts(testKey);
            await new Promise(resolve => setTimeout(resolve, 1100));
            expect(rateLimiter.getTimeUntilReset(testKey)).toBe(0);
        });
    });

    describe('getTimeUntilUnblock', () => {
        it('should return null when not blocked', () => {
            expect(rateLimiter.getTimeUntilUnblock(testKey)).toBeNull();
        });

        it('should return correct time until unblock when blocked', () => {
            // Exceed limit to trigger block
            for (let i = 0; i < 4; i++) {
                try {
                    rateLimiter.checkRateLimit(testKey);
                    rateLimiter.incrementAttempts(testKey);
                } catch (error) {
                    // Expected error on last attempt
                }
            }

            const timeUntilUnblock = rateLimiter.getTimeUntilUnblock(testKey);
            expect(timeUntilUnblock).toBeGreaterThan(0);
            expect(timeUntilUnblock).toBeLessThanOrEqual(2000);
        });
    });

    describe('isBlocked', () => {
        it('should return false for new keys', () => {
            expect(rateLimiter.isBlocked(testKey)).toBe(false);
        });

        it('should return true when blocked', () => {
            // Exceed limit to trigger block
            for (let i = 0; i < 4; i++) {
                try {
                    rateLimiter.checkRateLimit(testKey);
                    rateLimiter.incrementAttempts(testKey);
                } catch (error) {
                    // Expected error on last attempt
                }
            }

            expect(rateLimiter.isBlocked(testKey)).toBe(true);
        });

        it('should return false after block expires', async () => {
            // Exceed limit to trigger block
            for (let i = 0; i < 4; i++) {
                try {
                    rateLimiter.checkRateLimit(testKey);
                    rateLimiter.incrementAttempts(testKey);
                } catch (error) {
                    // Expected error on last attempt
                }
            }

            expect(rateLimiter.isBlocked(testKey)).toBe(true);

            // Wait for block to expire
            await new Promise(resolve => setTimeout(resolve, 2100));

            expect(rateLimiter.isBlocked(testKey)).toBe(false);
        });
    });

    describe('resetState', () => {
        it('should reset attempts and blocked status', () => {
            // Exceed limit to trigger block
            for (let i = 0; i < 4; i++) {
                try {
                    rateLimiter.checkRateLimit(testKey);
                    rateLimiter.incrementAttempts(testKey);
                } catch (error) {
                    // Expected error on last attempt
                }
            }

            expect(rateLimiter.isBlocked(testKey)).toBe(true);
            expect(rateLimiter.getRemainingAttempts(testKey)).toBe(0);

            rateLimiter.resetState(testKey);

            expect(rateLimiter.isBlocked(testKey)).toBe(false);
            expect(rateLimiter.getRemainingAttempts(testKey)).toBe(3);
        });
    });
}); 