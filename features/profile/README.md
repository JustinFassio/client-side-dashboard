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

### Styling Guidelines

#### Button Patterns
All primary action buttons (e.g., "Save Changes", "Update Profile") should follow these styling rules:
```css
.action-button {
    background: var(--primary-color);
    color: var(--background-darker);  /* Critical for text contrast */
    border: none;
    padding: var(--spacing-sm) var(--spacing-lg);
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-base);
    cursor: pointer;
    transition: background-color var(--transition-fast);
}

.action-button:hover {
    background: var(--primary-hover);
    color: var(--background-darker);
    transform: translateY(-1px);
}

.action-button:disabled {
    background-color: var(--text-dim);
    cursor: not-allowed;
    opacity: 0.7;
}
```

Key styling principles:
1. Use `var(--background-darker)` for button text to ensure contrast against citron green
2. Maintain consistent padding using spacing variables
3. Include hover state with subtle transform effect
4. Use transition for smooth hover effects
5. Include disabled state styling

#### Theme Integration
- Import variables from dashboard: `@import '../../../../dashboard/styles/variables.css';`
- Use CSS variables for colors, spacing, and typography
- Follow dark theme color scheme for consistent UI

#### Responsive Design
- Use breakpoints at 768px and 480px
- Adjust grid layouts and padding for mobile
- Maintain button styling across all screen sizes

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

# Profile Feature Migration Guide

## Overview
The profile feature is being migrated from legacy endpoints to a new, more robust implementation. This document outlines the migration process and provides guidance for developers.

## Deprecated Components
The following components are marked for deprecation in version 1.0.0:

1. `includes/rest-api/class-athlete-dashboard-profile-endpoints.php`
   - Legacy profile endpoints class
   - Will be removed in future versions
   - Currently maintained for backward compatibility

2. `includes/rest-api.php` profile endpoints
   - Legacy REST API endpoints
   - Will be removed in future versions
   - Currently maintained for backward compatibility

## New Implementation
The new profile feature implementation is located in:
- `features/profile/api/class-profile-endpoints.php`
- Namespace: `AthleteProfile\API`
- Main class: `ProfileEndpoints`

### Endpoint Mapping
Legacy endpoints are being replaced with the following new endpoints:

| Legacy Endpoint | New Endpoint |
|----------------|--------------|
| `custom/v1/profile` | `athlete-dashboard/v1/profile/full` |
| `athlete-dashboard/v1/profile` | `athlete-dashboard/v1/profile/basic` |

### Migration Steps
1. Update any custom code to use the new `AthleteProfile\API\ProfileEndpoints` class
2. Replace legacy endpoint URLs with their new counterparts
3. Test thoroughly with both implementations during transition
4. Monitor debug logs for any remaining usage of deprecated endpoints

### Debug Logging
Debug logs have been added to track usage of deprecated endpoints. Enable `WP_DEBUG` to view these logs.

Example debug log messages:
```
[Deprecated] Legacy profile endpoints initialized
[Deprecated] Legacy get_profile endpoint called
```

## Timeline
- Phase 1 (Current): Documentation and deprecation notices
- Phase 2: Feature parity and testing
- Phase 3: Frontend migration
- Phase 4: Legacy code removal

## Support
For any issues or questions during migration, please:
1. Check the debug logs for deprecated endpoint usage
2. Review the new implementation in `features/profile/api/`
3. Test endpoints using the provided test routes
4. Contact the development team for assistance 