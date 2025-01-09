# User Feature

## Overview
The User feature manages user data, preferences, and settings beyond basic authentication. It provides a centralized system for user management, role-based access control, and user-specific settings across the Athlete Dashboard.

## Configuration
```typescript
interface UserConfig {
    enabled: boolean;
    roles: {
        athlete: boolean;
        coach: boolean;
        admin: boolean;
    };
    preferences: {
        defaultTheme: 'light' | 'dark' | 'system';
        defaultLanguage: string;
        notifications: {
            email: boolean;
            push: boolean;
            inApp: boolean;
        };
    };
    permissions: {
        canInviteUsers: boolean;
        canManageWorkouts: boolean;
        canViewAnalytics: boolean;
    };
}
```

## API Endpoints

### Base Path
```
/wp-json/athlete-dashboard/v1/user
```

### Endpoints

#### GET /user
- **Purpose**: Retrieve current user data and settings
- **Authentication**: Required
- **Response**:
  ```typescript
  interface UserResponse {
      success: boolean;
      data: {
          user: {
              id: number;
              roles: string[];
              preferences: UserPreferences;
              permissions: UserPermissions;
              settings: UserSettings;
          };
      };
  }
  ```

#### PATCH /user/preferences
- **Purpose**: Update user preferences
- **Authentication**: Required
- **Parameters**:
  ```typescript
  interface PreferencesUpdateRequest {
      theme?: 'light' | 'dark' | 'system';
      language?: string;
      notifications?: {
          email?: boolean;
          push?: boolean;
          inApp?: boolean;
      };
  }
  ```
- **Error Codes**:
  - `400`: Invalid preferences data
  - `401`: Unauthorized
  - `500`: Update failed

## Events/Actions

### WordPress Actions
```php
// Fired when user preferences are updated
do_action('athlete_dashboard_user_preferences_updated', $user_id, $preferences);

// Fired when user permissions change
do_action('athlete_dashboard_user_permissions_changed', $user_id, $new_permissions);
```

### TypeScript Events
```typescript
enum UserEvent {
    PREFERENCES_UPDATED = 'USER_PREFERENCES_UPDATED',
    PERMISSIONS_CHANGED = 'USER_PERMISSIONS_CHANGED',
    SETTINGS_UPDATED = 'USER_SETTINGS_UPDATED',
    ROLE_CHANGED = 'USER_ROLE_CHANGED'
}
```

## Components

### Main Components
- `UserSettings`: User settings management interface
  ```typescript
  interface UserSettingsProps {
      onSave: (settings: UserSettings) => Promise<void>;
      initialSettings: UserSettings;
  }
  ```
- `UserPermissions`: Permission management component
  ```typescript
  interface UserPermissionsProps {
      userId: number;
      onUpdate: (permissions: UserPermissions) => Promise<void>;
  }
  ```

### Hooks
- `useUser`: Access user data and methods
  ```typescript
  function useUser(): {
      user: User | null;
      preferences: UserPreferences;
      updatePreferences: (prefs: Partial<UserPreferences>) => Promise<void>;
      permissions: UserPermissions;
      settings: UserSettings;
  }
  ```

## Dependencies

### External
- @wordpress/api-fetch
- @wordpress/hooks
- @wordpress/i18n

### Internal
- AuthContext (from auth feature)
- StorageService (from dashboard/services)
- ValidationUtils (from dashboard/utils)

## Testing

### Unit Tests
```bash
# Run user feature tests
npm run test features/user
```

### Integration Tests
```bash
# Run user integration tests
npm run test:integration features/user
```

## Error Handling

### Error Types
```typescript
enum UserErrorCodes {
    INVALID_PREFERENCES = 'USER_INVALID_PREFERENCES',
    UPDATE_FAILED = 'USER_UPDATE_FAILED',
    PERMISSION_DENIED = 'USER_PERMISSION_DENIED',
    SETTINGS_INVALID = 'USER_SETTINGS_INVALID'
}
```

### Error Recovery
- Automatic retry for preference updates
- Local storage backup for settings
- Fallback to default preferences
- Graceful permission degradation

## Performance Considerations
- Cached user preferences
- Lazy loading of user settings
- Batched permission updates
- Efficient role checking

## Security
- Role-based access control
- Permission validation
- Input sanitization
- Secure preference storage
- XSS prevention in user data

## Changelog
- 1.2.0: Added role-based permissions
- 1.1.0: Enhanced preference management
- 1.0.1: Security improvements
- 1.0.0: Initial release

### Styling Guidelines

#### Button Patterns
All primary action buttons (e.g., "Update User", "Save Preferences") should follow these styling rules:
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
``` 