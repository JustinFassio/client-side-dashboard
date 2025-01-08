# Auth Services

## RateLimiter

The RateLimiter service provides rate limiting functionality to protect authentication endpoints from brute force attacks and abuse. It implements a token bucket algorithm with blocking capabilities.

### Features

- Configurable attempt limits and time windows
- Automatic blocking after exceeding limits
- Customizable block duration
- Thread-safe singleton implementation
- Memory-based state management

### Usage

```typescript
// Get the RateLimiter instance with custom configuration
const rateLimiter = RateLimiter.getInstance({
    maxAttempts: 5,
    windowMs: 15 * 60 * 1000,    // 15 minutes
    blockDurationMs: 60 * 60 * 1000 // 1 hour
});

// Check rate limit before processing a request
try {
    rateLimiter.checkRateLimit('user:123');
    // Process the request
    rateLimiter.incrementAttempts('user:123');
} catch (error) {
    if (error instanceof AuthServiceError && error.code === AuthErrorCode.RATE_LIMIT_EXCEEDED) {
        // Handle rate limit exceeded
        const timeUntilUnblock = error.details?.timeUntilUnblock;
        console.log(`Please try again in ${Math.ceil(timeUntilUnblock / 1000)} seconds`);
    }
}

// Get remaining attempts
const remaining = rateLimiter.getRemainingAttempts('user:123');

// Check if blocked
const isBlocked = rateLimiter.isBlocked('user:123');

// Get time until rate limit window reset
const timeUntilReset = rateLimiter.getTimeUntilReset('user:123');

// Get time until unblock (if blocked)
const timeUntilUnblock = rateLimiter.getTimeUntilUnblock('user:123');
```

### Configuration

The RateLimiter accepts the following configuration options:

- `maxAttempts`: Maximum number of attempts allowed within the time window
- `windowMs`: Time window in milliseconds
- `blockDurationMs`: Duration of the block in milliseconds when limit is exceeded

Default configuration:
```typescript
{
    maxAttempts: 5,
    windowMs: 15 * 60 * 1000,    // 15 minutes
    blockDurationMs: 60 * 60 * 1000 // 1 hour
}
```

### Methods

#### `getInstance(config?: Partial<RateLimiterConfig>): RateLimiter`
Gets the singleton instance of the RateLimiter. Optionally accepts configuration options.

#### `checkRateLimit(key: string): void`
Checks if an action is allowed for a given key. Throws `AuthServiceError` if rate limit is exceeded.

#### `incrementAttempts(key: string): void`
Increments the attempt counter for a given key.

#### `getRemainingAttempts(key: string): number`
Gets the remaining attempts for a given key.

#### `getTimeUntilReset(key: string): number`
Gets the time in milliseconds until the rate limit window resets.

#### `getTimeUntilUnblock(key: string): number | null`
Gets the time in milliseconds until the block expires. Returns null if not blocked.

#### `isBlocked(key: string): boolean`
Checks if a key is currently blocked.

#### `resetState(key: string): void`
Resets the rate limit state for a given key.

### Error Handling

The RateLimiter throws `AuthServiceError` with code `RATE_LIMIT_EXCEEDED` when limits are exceeded. The error includes details about the time until unblock in the `details` property.

### Testing

The RateLimiter includes comprehensive unit tests covering all functionality. Run the tests using:

```bash
npm run test features/auth/services/__tests__/rateLimiting.test.ts
```

### Best Practices

1. Use unique keys that combine the action type and user identifier:
   ```typescript
   const key = `login:${username}`;
   ```

2. Always increment attempts after processing the request:
   ```typescript
   try {
       rateLimiter.checkRateLimit(key);
       // Process request
       rateLimiter.incrementAttempts(key);
   } catch (error) {
       // Handle error
   }
   ```

3. Include remaining attempts in error responses:
   ```typescript
   const remaining = rateLimiter.getRemainingAttempts(key);
   response.headers['X-RateLimit-Remaining'] = remaining.toString();
   ```

4. Use appropriate time windows and block durations based on the sensitivity of the action:
   ```typescript
   // More strict limits for password reset
   const rateLimiter = RateLimiter.getInstance({
       maxAttempts: 3,
       windowMs: 5 * 60 * 1000,    // 5 minutes
       blockDurationMs: 2 * 60 * 60 * 1000 // 2 hours
   });
   