# Authentication Feature

## Overview
The Authentication feature manages user authentication, session handling, and access control within the Athlete Dashboard. It provides a secure, event-driven authentication system that integrates with WordPress's authentication mechanisms while adding custom functionality for athlete-specific requirements.

## Components

### AuthFeature
The main feature component that initializes authentication handling and manages the authentication lifecycle.

```typescript
import { AuthFeature } from './AuthFeature';
const authFeature = new AuthFeature();
authFeature.register();
```

### AuthContext
Provides authentication state and methods throughout the application.

```typescript
import { useAuth } from './context/AuthContext';

function MyComponent() {
    const { isAuthenticated, user, login, logout } = useAuth();
    // ... use auth state and methods
}
```

### Services

#### AuthService
Handles authentication operations and API interactions.

```typescript
import { AuthService } from './services/AuthService';

// Login user
await AuthService.login(credentials);

// Logout user
await AuthService.logout();

// Check authentication status
const isAuthenticated = await AuthService.checkAuth();
```

## Events

### Emitted Events
- `AUTH_LOGIN_SUCCESS`: Emitted when a user successfully logs in
- `AUTH_LOGOUT`: Emitted when a user logs out
- `AUTH_SESSION_EXPIRED`: Emitted when the user's session expires
- `AUTH_ERROR`: Emitted when an authentication error occurs

### Handled Events
- `FEATURE_LOADED`: Initializes authentication state
- `NAVIGATION_CHANGED`: Updates authentication state based on route changes

## WordPress Integration

### REST API Endpoints
The feature registers custom REST API endpoints for authentication:

```php
/athlete-dashboard/v1/auth/login
/athlete-dashboard/v1/auth/logout
/athlete-dashboard/v1/auth/status
```

### Nonce Handling
All authenticated requests require WordPress nonces:

```typescript
const headers = {
    'X-WP-Nonce': wp_rest.nonce
};
```

## Security Features

1. **Session Management**
   - Secure session handling
   - Automatic session refresh
   - Session timeout handling

2. **Access Control**
   - Role-based access control
   - Permission checking
   - Protected route handling

3. **Security Headers**
   - CSRF protection
   - XSS prevention
   - Content Security Policy

## Usage Examples

### Basic Authentication Flow
```typescript
import { useAuth } from './context/AuthContext';

function LoginComponent() {
    const { login, error } = useAuth();

    const handleLogin = async (credentials) => {
        try {
            await login(credentials);
            // Handle successful login
        } catch (err) {
            // Handle login error
        }
    };
}
```

### Protected Route
```typescript
import { useAuth } from './context/AuthContext';
import { Navigate } from 'react-router-dom';

function ProtectedRoute({ children }) {
    const { isAuthenticated, isLoading } = useAuth();

    if (isLoading) {
        return <LoadingSpinner />;
    }

    if (!isAuthenticated) {
        return <Navigate to="/login" />;
    }

    return children;
}
```

### Event Handling
```typescript
import { EventEmitter } from '@/dashboard/events';

// Listen for authentication events
EventEmitter.on('AUTH_LOGIN_SUCCESS', (user) => {
    // Handle successful login
});

EventEmitter.on('AUTH_SESSION_EXPIRED', () => {
    // Handle session expiration
});
```

## Error Handling

### Common Error Codes
- `invalid_credentials`: Invalid username or password
- `session_expired`: User session has expired
- `insufficient_permissions`: User lacks required permissions
- `auth_required`: Authentication required for this action

### Error Response Format
```json
{
    "code": "error_code",
    "message": "Human-readable error message",
    "data": {
        // Additional error details
    }
}
```

## Configuration

### Default Settings
```typescript
const DEFAULT_CONFIG = {
    sessionTimeout: 3600,
    refreshThreshold: 300,
    maxRetries: 3
};
```

### Customization
Override default settings in your theme:

```php
add_filter('athlete_dashboard_auth_config', function($config) {
    return array_merge($config, [
        'sessionTimeout' => 7200,
        'refreshThreshold' => 600
    ]);
});
```

## Development

### Testing
Run authentication tests:
```bash
npm run test:auth
```

### Adding New Authentication Methods
1. Create a new authentication provider
2. Implement required interfaces
3. Register the provider with AuthService
4. Add corresponding REST API endpoints

## Troubleshooting

### Common Issues
1. **Session Expiration**
   - Check session timeout settings
   - Verify refresh token handling

2. **CORS Issues**
   - Verify allowed origins
   - Check credentials handling

3. **WordPress Integration**
   - Validate nonce configuration
   - Check WordPress user roles

### Debugging
Enable debug mode for detailed logging:

```php
define('AUTH_DEBUG', true);
```

## Need Help?
- Check error responses for detailed messages
- Review authentication logs
- Contact the development team
``` 