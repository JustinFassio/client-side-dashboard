# User Feature

## Overview
The User feature manages user data, preferences, and settings beyond basic authentication. It provides a centralized system for user management, role-based access control, and user-specific settings across the Athlete Dashboard.

## Integration with Dashboard Shell

The User feature integrates with the dashboard shell through minimal touch points:

1. **Context Provider**
```typescript
// Provides user data to the entire application
<UserProvider>
    {children}
</UserProvider>
```

2. **Feature Registration**
```typescript
class UserFeature extends Feature {
    identifier = 'user';
    async register() {
        this.registerRoutes();
        this.registerEventHandlers();
    }
}
```

3. **Event System**
```typescript
// Publishing user events
Events.publish('user:updated', { userId, data });

// Subscribing to user events
Events.subscribe('user:updated', handleUserUpdate);
```

## Core Components

### UserContext
Provides user state management with minimal external dependencies:
```typescript
interface UserContextValue {
    user: User | null;
    isLoading: boolean;
    error: Error | null;
    isAuthenticated: boolean;
    checkAuth: () => Promise<boolean>;
    logout: () => Promise<void>;
    refreshUser: () => Promise<void>;
}
```

### AuthService
Handles authentication with WordPress:
```typescript
class AuthService {
    async getCurrentUser(): Promise<User>;
    async logout(): Promise<void>;
}
```

## API Endpoints

### Base Path
```
/wp-json/athlete-dashboard/v1/user
```

### Endpoints

#### GET /user
- **Purpose**: Retrieve current user data
- **Authentication**: Required
- **Response**:
  ```typescript
  interface UserResponse {
      success: boolean;
      data: {
          user: User;
      };
  }
  ```

## Types

### User
```typescript
interface User {
    id: number;
    username: string;
    email: string;
    displayName: string;
    firstName: string;
    lastName: string;
    roles: string[];
}
```

## Best Practices

1. **State Management**
   - Use UserContext for user state
   - Handle loading states appropriately
   - Implement proper error handling

2. **Authentication**
   - Use AuthService for WordPress integration
   - Handle session management properly
   - Implement secure token handling

3. **Event Usage**
   - Publish user state changes
   - Subscribe to relevant user events
   - Document event contracts

## Example Usage

### Using UserContext
```typescript
import { useUser } from '../context/UserContext';

function MyComponent() {
    const { user, isLoading, error } = useUser();
    
    if (isLoading) return <LoadingState />;
    if (error) return <ErrorMessage error={error} />;
    if (!user) return <NotAuthenticated />;
    
    return <div>Welcome, {user.displayName}!</div>;
}
```

### Handling User Events
```typescript
import { Events } from '../../../dashboard/core/events';

// Subscribe to user updates
Events.subscribe('user:updated', (data) => {
    console.log('User updated:', data);
});

// Publish user events
Events.publish('user:updated', {
    userId: user.id,
    changes: updatedFields
});
``` 