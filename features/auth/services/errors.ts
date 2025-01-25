import { AuthErrorCode } from '../types';

export class AuthServiceError extends Error {
    constructor(
        public readonly code: AuthErrorCode,
        message: string,
        public readonly details?: Record<string, any>
    ) {
        super(message);
        this.name = 'AuthServiceError';
    }
}

export function shouldRetry(error: unknown): boolean {
    // Don't retry on authentication or validation errors
    if (error instanceof AuthServiceError) {
        const nonRetryableCodes = [
            AuthErrorCode.INVALID_CREDENTIALS,
            AuthErrorCode.INVALID_INVITE_CODE,
            AuthErrorCode.INVALID_RESPONSE
        ];
        return !nonRetryableCodes.includes(error.code);
    }

    // Retry on network errors or unknown errors
    return true;
} 