# Profile Feature

## Overview
The Profile feature manages athlete profile data, including personal information, preferences, and training settings. It provides a complete interface for users to view and update their profile information while maintaining data consistency across the application.

## Configuration
```typescript
interface ProfileConfig {
    enabled: boolean;
    fields: {
        required: string[];
        optional: string[];
    };
    validation: {
        age: {
            min: number;
            max: number;
        };
    };
}
```

## API Endpoints

### Base Path
```
/wp-json/athlete-dashboard/v1/profile
```

### Endpoints

#### GET /profile
- **Purpose**: Retrieve user profile data
- **Authentication**: Required
- **Response**:
  ```typescript
  interface ProfileResponse {
      success: boolean;
      data: {
          profile: {
              age: number;
              firstName: string;
              lastName: string;
              email: string;
              preferences: UserPreferences;
          };
      };
  }
  ```

#### POST /profile
- **Purpose**: Update user profile data
- **Authentication**: Required
- **Parameters**:
  ```typescript
  interface ProfileUpdateRequest {
      age?: number;
      firstName?: string;
      lastName?: string;
      preferences?: Partial<UserPreferences>;
  }
  ```
- **Error Codes**:
  - `400`: Invalid profile data
  - `401`: Unauthorized
  - `500`: Server error

## Events/Actions

### WordPress Actions
```php
// Fired when profile is updated
do_action('athlete_dashboard_profile_updated', $user_id, $profile_data);

// Fired when profile is loaded
do_action('athlete_dashboard_profile_loaded', $user_id);
```

### TypeScript Events
```typescript
enum ProfileEvent {
    FETCH_REQUEST = 'PROFILE_FETCH_REQUEST',
    FETCH_SUCCESS = 'PROFILE_FETCH_SUCCESS',
    FETCH_ERROR = 'PROFILE_FETCH_ERROR',
    UPDATE_REQUEST = 'PROFILE_UPDATE_REQUEST',
    UPDATE_SUCCESS = 'PROFILE_UPDATE_SUCCESS',
    UPDATE_ERROR = 'PROFILE_UPDATE_ERROR'
}
```

## Components

### Main Components
- `ProfileLayout`: Main profile page layout
  ```typescript
  interface ProfileLayoutProps {
      context: FeatureContext;
  }
  ```
- `ProfileForm`: Profile editing form
  ```typescript
  interface ProfileFormProps {
      onSubmit: (data: ProfileUpdateRequest) => Promise<void>;
      initialData?: ProfileData;
  }
  ```

### Hooks
- `useProfile`: Access and manage profile data
  ```typescript
  function useProfile(): {
      profile: ProfileData | null;
      loading: boolean;
      error: Error | null;
      updateProfile: (data: ProfileUpdateRequest) => Promise<void>;
  }
  ```

## Dependencies

### External
- @wordpress/api-fetch
- @wordpress/hooks
- react-hook-form

### Internal
- UserContext (from user feature)
- ErrorBoundary (from dashboard/components)
- ValidationUtils (from dashboard/utils)

## Testing

### Unit Tests
```bash
# Run profile feature tests
npm run test features/profile
```

### Integration Tests
```bash
# Run profile integration tests
npm run test:integration features/profile
```

## Error Handling

### Error Types
```typescript
enum ProfileErrorCodes {
    INVALID_DATA = 'PROFILE_INVALID_DATA',
    UPDATE_FAILED = 'PROFILE_UPDATE_FAILED',
    FETCH_FAILED = 'PROFILE_FETCH_FAILED'
}
```

### Error Recovery
- Automatic retry on network failures
- Form data persistence on browser refresh
- Optimistic updates with rollback

## Performance Considerations
- Profile data is cached using WordPress transients
- Lazy loading of non-critical profile sections
- Debounced form updates
- Optimistic UI updates

## Security
- All endpoints require authentication
- Data validation on both client and server
- XSS prevention through WordPress escaping
- CSRF protection via WordPress nonces

## Changelog
- 1.1.0: Added profile image support
- 1.0.1: Fixed validation issues
- 1.0.0: Initial release 