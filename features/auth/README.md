# Auth Feature

## Overview
The Auth feature manages user authentication, including login, logout, and registration processes. It provides secure authentication flows, session management, and integration with WordPress's authentication system while adding athlete-specific registration fields.

## Configuration
```typescript
interface AuthConfig {
    enabled: boolean;
    registration: {
        enabled: boolean;
        requireInviteCode: boolean;
    };
    security: {
        maxLoginAttempts: number;
        lockoutDuration: number;
        passwordStrength: 'weak' | 'medium' | 'strong';
    };
    redirects: {
        afterLogin: string;
        afterLogout: string;
        afterRegistration: string;
    };
}
```

## API Endpoints

### Base Path
```
/wp-json/athlete-dashboard/v1/auth
```

### Endpoints

#### POST /login
- **Purpose**: Authenticate user and create session
- **Authentication**: Not Required
- **Parameters**:
  ```typescript
  interface LoginRequest {
      username: string;
      password: string;
      rememberMe?: boolean;
  }
  ```
- **Response**:
  ```typescript
  interface LoginResponse {
      success: boolean;
      data: {
          token: string;
          user: {
              id: number;
              username: string;
              email: string;
              roles: string[];
          };
      };
  }
  ```

#### POST /register
- **Purpose**: Create new user account
- **Authentication**: Not Required
- **Parameters**:
  ```typescript
  interface RegisterRequest {
      username: string;
      email: string;
      password: string;
      firstName: string;
      lastName: string;
      inviteCode?: string;
  }
  ```
- **Error Codes**:
  - `400`: Invalid registration data
  - `401`: Invalid invite code
  - `409`: Username/email already exists
  - `500`: Registration failed

#### POST /logout
- **Purpose**: End user session
- **Authentication**: Required
- **Response**: Success message

## Events/Actions

### WordPress Actions
```php
// Fired on successful login
do_action('athlete_dashboard_auth_login_success', $user_id);

// Fired on successful registration
do_action('athlete_dashboard_auth_registration_success', $user_id);

// Fired on logout
do_action('athlete_dashboard_auth_logout', $user_id);
```

### TypeScript Events
```typescript
enum AuthEvent {
    LOGIN_REQUEST = 'AUTH_LOGIN_REQUEST',
    LOGIN_SUCCESS = 'AUTH_LOGIN_SUCCESS',
    LOGIN_ERROR = 'AUTH_LOGIN_ERROR',
    REGISTER_REQUEST = 'AUTH_REGISTER_REQUEST',
    REGISTER_SUCCESS = 'AUTH_REGISTER_SUCCESS',
    REGISTER_ERROR = 'AUTH_REGISTER_ERROR',
    LOGOUT = 'AUTH_LOGOUT'
}
```

## Components

### Main Components
- `LoginForm`: Handles user login
  ```typescript
  interface LoginFormProps {
      onSuccess?: (user: User) => void;
      onError?: (error: Error) => void;
      redirectUrl?: string;
  }
  ```
- `RegistrationForm`: Handles user registration
  ```typescript
  interface RegistrationFormProps {
      onSuccess?: (user: User) => void;
      onError?: (error: Error) => void;
      requireInviteCode?: boolean;
  }
  ```

### Hooks
- `useAuth`: Access authentication state and methods
  ```typescript
  function useAuth(): {
      user: User | null;
      isAuthenticated: boolean;
      login: (credentials: LoginCredentials) => Promise<void>;
      logout: () => Promise<void>;
      register: (data: RegistrationData) => Promise<void>;
  }
  ```

## Dependencies

### External
- @wordpress/api-fetch
- @wordpress/hooks
- jwt-decode

### Internal
- ValidationUtils (from dashboard/utils)
- SecurityService (from dashboard/services)
- ErrorBoundary (from dashboard/components)

## Testing

### Unit Tests
```bash
# Run auth feature tests
npm run test features/auth
```

### Integration Tests
```bash
# Run auth integration tests
npm run test:integration features/auth
```

## Error Handling

### Error Types
```typescript
enum AuthErrorCodes {
    INVALID_CREDENTIALS = 'AUTH_INVALID_CREDENTIALS',
    REGISTRATION_FAILED = 'AUTH_REGISTRATION_FAILED',
    INVALID_INVITE_CODE = 'AUTH_INVALID_INVITE_CODE',
    SESSION_EXPIRED = 'AUTH_SESSION_EXPIRED',
    RATE_LIMIT_EXCEEDED = 'AUTH_RATE_LIMIT_EXCEEDED'
}
```

### Error Recovery
- Automatic token refresh for expired sessions
- Rate limiting recovery with exponential backoff
- Form data persistence on failed submissions
- Clear error messages with recovery instructions

## Performance Considerations
- Token-based authentication for API requests
- Caching of user permissions
- Minimal session data storage
- Efficient form validation

## Security
- CSRF protection
- Password strength enforcement
- Rate limiting on login attempts
- Secure session management
- Input sanitization and validation
- Invite code system for registration control

## Changelog
- 1.2.0: Added invite code system
- 1.1.0: Implemented rate limiting
- 1.0.1: Security enhancements
- 1.0.0: Initial release
``` 