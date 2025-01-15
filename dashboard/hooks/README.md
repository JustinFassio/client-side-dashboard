# Dashboard Hooks

This directory contains React hooks used throughout the dashboard feature. These hooks provide reusable functionality for components and features.

## Available Hooks

### `useUser`

A hook to access the current user's data and authentication state. This hook must be used within a `UserProvider` context.

```typescript
import { useUser } from '../../hooks/useUser';

function MyComponent({ context }) {
    const { user, isLoading, error } = useUser(context);
    
    if (isLoading) return <div>Loading...</div>;
    if (error) return <div>Error: {error}</div>;
    if (!user) return <div>Not authenticated</div>;
    
    return <div>Welcome, {user.name}!</div>;
}
```

#### Returns

The hook returns an object with the following properties:

- `user`: The current user's data (or `null` if not authenticated)
  ```typescript
  {
    id: number;
    name: string;
    email: string;
    roles: string[];
  }
  ```
- `isLoading`: Boolean indicating if user data is being fetched
- `error`: Error message if something went wrong (or `null`)

#### Requirements

- Must be used within a `UserProvider` context
- Requires a `FeatureContext` parameter
- Component must handle loading and error states appropriately

#### Integration with UserContext

This hook integrates with the User feature's context system:
- Uses the centralized `UserContext` for state management
- Automatically handles authentication state
- Provides consistent user data across the application

## Best Practices

1. **Error Handling**
   - Always handle the error state
   - Show appropriate error messages to users
   - Consider implementing retry logic for transient errors

2. **Loading States**
   - Show loading indicators while data is being fetched
   - Maintain UI consistency during loading
   - Consider skeleton loaders for better UX

3. **Type Safety**
   - Use TypeScript interfaces for user data
   - Handle null states appropriately
   - Validate user roles when needed

## Example Usage

```typescript
import React from 'react';
import { useUser } from '../../hooks/useUser';
import { FeatureContext } from '../../contracts/Feature';

interface MyComponentProps {
    context: FeatureContext;
}

export const MyComponent: React.FC<MyComponentProps> = ({ context }) => {
    const { user, isLoading, error } = useUser(context);

    if (isLoading) {
        return <LoadingSpinner />;
    }

    if (error) {
        return <ErrorMessage message={error} />;
    }

    if (!user) {
        return <NotAuthenticated />;
    }

    return (
        <div>
            <h1>Welcome, {user.name}!</h1>
            <div>Email: {user.email}</div>
            <div>Roles: {user.roles.join(', ')}</div>
        </div>
    );
};
``` 