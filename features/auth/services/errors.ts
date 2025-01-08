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