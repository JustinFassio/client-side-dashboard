# User Feature

## Overview
The User feature manages user data, preferences, and settings within the Athlete Dashboard. It provides a centralized system for handling user-related operations and state management, working in conjunction with the authentication and profile features.

## Components

### UserFeature
The main feature component that initializes user management and handles user-related operations.

```typescript
import { UserFeature } from './UserFeature';
const userFeature = new UserFeature();
userFeature.register();
```

### UserContext
Provides user state and methods throughout the application.

```typescript
import { useUser } from './context/UserContext';

function MyComponent() {
    const { user, updateUser, preferences } = useUser();
    // ... use user state and methods
}
```

### Services

#### UserService
Handles user operations and API interactions.

```typescript
import { UserService } from './services/UserService';

// Get user data
const user = await UserService.getCurrentUser();

// Update user preferences
await UserService.updatePreferences(preferences);

// Get user settings
const settings = await UserService.getUserSettings();
```

## Events

### Emitted Events
- `USER_UPDATED`: Emitted when user data is updated
- `USER_PREFERENCES_CHANGED`: Emitted when preferences are modified
- `USER_SETTINGS_CHANGED`: Emitted when settings are changed
- `USER_ERROR`: Emitted when a user-related error occurs

### Handled Events
- `AUTH_LOGIN_SUCCESS`: Initializes user data after login
- `PROFILE_UPDATED`: Updates user data when profile changes
- `FEATURE_LOADED`: Sets up user state

## Data Models

### User Interface
```typescript
interface User {
    id: number;
    username: string;
    email: string;
    roles: string[];
    preferences: UserPreferences;
    settings: UserSettings;
    meta: Record<string, any>;
}
```

### Preferences Interface
```typescript
interface UserPreferences {
    theme: 'light' | 'dark' | 'system';
    notifications: NotificationPreferences;
    dashboard: DashboardPreferences;
    workouts: WorkoutPreferences;
}
```

## WordPress Integration

### REST API Endpoints
The feature registers custom REST API endpoints for user management:

```php
/athlete-dashboard/v1/user/current
/athlete-dashboard/v1/user/preferences
/athlete-dashboard/v1/user/settings
```

### Meta Data Handling
User meta data is stored using WordPress user meta:

```php
// Get user meta
$preferences = get_user_meta($user_id, 'athlete_dashboard_preferences', true);

// Update user meta
update_user_meta($user_id, 'athlete_dashboard_preferences', $preferences);
```

## Usage Examples

### Managing User Preferences
```typescript
import { useUser } from './context/UserContext';

function ThemeSelector() {
    const { preferences, updatePreferences } = useUser();

    const handleThemeChange = async (theme) => {
        try {
            await updatePreferences({ theme });
            // Handle successful update
        } catch (err) {
            // Handle error
        }
    };
}
```

### User Settings Management
```typescript
import { useUser } from './context/UserContext';

function NotificationSettings() {
    const { settings, updateSettings } = useUser();

    const handleToggleNotification = async (type, enabled) => {
        try {
            await updateSettings({
                notifications: {
                    ...settings.notifications,
                    [type]: enabled
                }
            });
        } catch (err) {
            // Handle error
        }
    };
}
```

### Event Handling
```typescript
import { EventEmitter } from '@/dashboard/events';

// Listen for user events
EventEmitter.on('USER_UPDATED', (userData) => {
    // Handle user update
});

EventEmitter.on('USER_PREFERENCES_CHANGED', (preferences) => {
    // Handle preferences change
});
```

## Error Handling

### Common Error Codes
- `user_not_found`: User does not exist
- `invalid_preferences`: Invalid preference data
- `update_failed`: Failed to update user data
- `permission_denied`: User lacks required permissions

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
    preferencesSyncInterval: 5000,
    maxPreferencesSize: 100000,
    cacheTimeout: 300
};
```

### Customization
Override default settings in your theme:

```php
add_filter('athlete_dashboard_user_config', function($config) {
    return array_merge($config, [
        'preferencesSyncInterval' => 10000,
        'cacheTimeout' => 600
    ]);
});
```

## Development

### Testing
Run user feature tests:
```bash
npm run test:user
```

### Adding New User Features
1. Define new interfaces/types
2. Implement feature logic
3. Add REST API endpoints
4. Update user context
5. Add event handlers

## Troubleshooting

### Common Issues
1. **Data Synchronization**
   - Check sync interval settings
   - Verify event handling
   - Monitor network requests

2. **Performance Issues**
   - Review preferences size
   - Check caching configuration
   - Monitor meta data usage

3. **WordPress Integration**
   - Verify user capabilities
   - Check meta data storage
   - Review role assignments

### Debugging
Enable debug mode for detailed logging:

```php
define('USER_DEBUG', true);
```

## Need Help?
- Review error messages in the console
- Check user meta data in WordPress
- Contact the development team
``` 