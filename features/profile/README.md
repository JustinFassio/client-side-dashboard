# Profile Feature

## Overview
The Profile feature in the Athlete Dashboard is responsible for managing user profile data. It provides a modular, type-safe implementation for collecting, validating, and storing user data, including basic information, physical metrics, medical details, and account settings.

## Getting Started
1. Clone the repository
2. Navigate to the `features/profile/` directory
3. Run `npm install` to install dependencies
4. Run `npm test` to verify functionality
5. Start the dashboard using `npm start` and navigate to `/dashboard?dashboard_feature=profile`

## Architecture

### Directory Structure
```plaintext
features/profile/
├── components/
│   ├── form/                    # Form components
│   │   ├── fields/             # Form field components
│   │   ├── sections/           # Form section components
│   │   └── ProfileForm.tsx     # Main form container
│   ├── InjuryTracker/          # Injury tracking feature
│   └── PhysicalMetricsDisplay/ # Physical metrics display
├── services/
│   ├── ProfileService.ts       # Profile data and API management
│   └── ValidationService.ts    # Form validation logic
├── context/
│   └── ProfileContext.tsx      # Profile state management
├── events/
│   └── events.ts              # Event definitions
├── types/
│   ├── profile.ts             # Type definitions
│   └── validation.ts          # Validation types
├── api/
│   └── profile-endpoints.php  # WordPress API endpoints
└── __tests__/                 # Feature tests
```

## Key Components

### ProfileForm
- Manages form state and section navigation
- Implements real-time validation
- Handles data persistence and error states
- Uses a tabbed interface for section organization

### Form Sections
1. **BasicSection**: Core user information
   - Display Name
   - Email
   - Basic Demographics

2. **PhysicalSection**: Physical metrics
   - Height
   - Weight
   - Fitness Level
   - Activity Level

3. **MedicalSection**: Health information
   - Medical Conditions
   - Exercise Limitations
   - Medications

4. **InjuryTracker**: Injury management
   - Current Injuries
   - Past Injuries
   - Recovery Progress
   - Exercise Modifications

5. **AccountSection**: Account settings
   - Username
   - Password
   - Preferences

### Cross-Component Interaction
The Profile feature interacts with other dashboard features through:

1. **Workout Generator**
   - Provides fitness level and limitations for workout customization
   - Syncs injury data for exercise modifications

2. **Progress Tracker**
   - Shares physical metrics for progress visualization
   - Updates weight and fitness level based on completed workouts

3. **Nutrition Planner**
   - Provides height, weight, and activity level for caloric calculations
   - Syncs dietary restrictions from medical conditions

### Services

#### ProfileService
- Manages API interactions
- Implements caching for performance
- Handles data normalization
- Emits events for state changes
- Includes performance monitoring

#### ValidationService
- Provides field-level validation
- Implements cross-field validation rules
- Supports custom validation rules

### Context System
The feature uses React Context (`ProfileContext`) for centralized state management:
- Manages profile data state and loading states
- Handles API interactions through ProfileService
- Provides form validation state
- Manages error handling and notifications

```typescript
import { useProfile } from '../../context/ProfileContext';

function ProfileComponent() {
    const { 
        profileData,      // Current profile state
        updateProfile,    // Method to update profile
        isLoading,       // Loading state
        validationErrors // Current validation errors
    } = useProfile();
    // Use profile data and methods
}
```

### Event System
Events are defined in the `events/` directory with a modular structure:

```plaintext
events/
├── index.ts           # Main export file
├── types.ts          # Event type definitions
├── constants.ts      # Event constant definitions
├── utils.ts          # Event utilities
└── compatibility.ts  # Legacy compatibility layer
```

Events are used for cross-component communication and state updates:

```typescript
import { PROFILE_EVENTS, emitProfileEvent, onProfileEvent } from './events';

// Emit an event
emitProfileEvent(PROFILE_EVENTS.UPDATE_SUCCESS, updatedProfile);

// Listen for events
onProfileEvent(PROFILE_EVENTS.UPDATE_SUCCESS, (profile) => {
    // Handle profile update
});
```

Available events:
```typescript
PROFILE_EVENTS = {
    // Data fetching events
    FETCH_REQUEST: 'profile:fetch-request',
    FETCH_SUCCESS: 'profile:fetch-success',
    FETCH_ERROR: 'profile:fetch-error',

    // Update events
    UPDATE_REQUEST: 'profile:update-request',
    UPDATE_SUCCESS: 'profile:update-success',
    UPDATE_ERROR: 'profile:update-error',

    // UI events
    SECTION_CHANGE: 'profile:section-change',
    VALIDATION_ERROR: 'profile:validation-error',
    FORM_RESET: 'profile:form-reset',

    // Injury tracking events
    INJURY_ADDED: 'profile:injury-added',
    INJURY_UPDATED: 'profile:injury-updated',
    INJURY_REMOVED: 'profile:injury-removed'
}
```

Example of adding a new custom event:
```typescript
// 1. Add to PROFILE_EVENTS in constants.ts
export const PROFILE_EVENTS = {
    // ... existing events ...
    GOALS_UPDATED: 'profile:goals-updated'
} as const;

// 2. Update types in types.ts
export type ProfileEvent = 
    // ... existing types ...
    | { type: typeof PROFILE_EVENTS.GOALS_UPDATED; goals: string[] };

export type ProfileEventPayloads = {
    // ... existing payloads ...
    [PROFILE_EVENTS.GOALS_UPDATED]: string[];
};

// 3. Use in components
emitProfileEvent(PROFILE_EVENTS.GOALS_UPDATED, ['weight_loss', 'muscle_gain']);
```

To extend available events:
1. Add new event type to `events/constants.ts`
2. Update event types in `events/types.ts`
3. Add event handler in relevant components
4. Update compatibility layer in `events/compatibility.ts` if needed

## WordPress Integration
### Data Storage
- Profile data stored in WordPress user meta
- Custom meta fields prefixed with `athlete_`
- Automatic data sanitization and validation

### Security
- Nonce validation implemented in `profile-endpoints.php`
- Capability checks for data access
- Input sanitization on both client and server

### API Endpoints
```php
// Available in profile-endpoints.php
/wp-json/athlete-dashboard/v1/profile/
├── GET  /              # Fetch profile
├── POST /              # Update profile
├── GET  /basic         # Fetch basic info
└── POST /user          # Update user data
```

Example API Requests/Responses:

```json
// GET /wp-json/athlete-dashboard/v1/profile/basic
{
    "success": true,
    "data": {
        "displayName": "John Doe",
        "email": "john@example.com",
        "activityLevel": "moderately_active",
        "fitnessLevel": "intermediate"
    }
}

// POST /wp-json/athlete-dashboard/v1/profile
Request:
{
    "height": 175,
    "weight": 70,
    "fitnessGoals": ["strength", "endurance"]
}

Response:
{
    "success": true,
    "data": {
        "profile": {
            "height": 175,
            "weight": 70,
            "fitnessGoals": ["strength", "endurance"],
            "updatedAt": "2024-01-20T15:30:00Z"
        }
    }
}

// Error Response
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Invalid height value",
        "details": {
            "height": ["Must be between 100 and 250 cm"]
        }
    }
}
```

## Usage

### Basic Implementation
```typescript
import { ProfileForm } from './components/form/ProfileForm';

function App() {
    return (
        <div>
            <h1>User Profile</h1>
            <ProfileForm />
        </div>
    );
}
```

### Using Profile Context
```typescript
import { useProfile } from './context/ProfileContext';

function ProfileDisplay() {
    const { profileData, isLoading } = useProfile();
    
    if (isLoading) return <div>Loading...</div>;
    
    return <div>{profileData.displayName}</div>;
}
```

## Data Flow

1. **Initial Load**
   ```
   ProfileForm -> PROFILE_LOADING -> ProfileService
                  PROFILE_UPDATED -> Update UI
   ```

2. **Form Updates**
   ```
   Field Change -> Validation -> Update State
                   Emit Events -> Update UI
   ```

3. **Save Flow**
   ```
   Submit -> Validate -> Update Request
                     -> PROFILE_UPDATED/UPDATE_FAILED
   ```

## Development

### Adding New Fields
1. Add field type to `ProfileData` interface
2. Add validation rules in `ValidationService`
3. Update relevant form section
4. Add event handling if needed

### Testing
The feature includes comprehensive test coverage:

### Unit Tests
- Services (`services/__tests__/`)
  - API interaction tests
  - Data transformation tests
  - Validation logic tests

### Integration Tests
- Form interactions (`components/__tests__/`)
  - Section navigation
  - Form submission
  - Validation feedback

### Event Tests
- Event emission and handling (`events/__tests__/`)
  - Event payload validation
  - Event handler registration

```bash
# Run all profile feature tests
npm test features/profile

# Run specific test file
npm test features/profile/events/__tests__/events.test.ts
```

## Error Handling
- Form-level validation errors
- API error responses
- Network issues
- Cross-field validation errors

## Future Improvements

### High Priority
- [ ] Enhance accessibility
  - Add ARIA roles and labels
  - Improve keyboard navigation
  - Add screen reader support
- [ ] Implement field-level loading states
  - Add loading indicators for async operations
  - Improve feedback during saves

### Medium Priority
- [ ] Add optimistic updates
  - Update UI before API response
  - Handle rollback on failure
- [ ] Implement form state persistence
  - Save draft changes locally
  - Restore form state after navigation

### Low Priority
- [ ] Add analytics tracking
  - Track form completion rates
  - Monitor error frequencies
- [ ] Enhance performance monitoring
  - Track layout shifts
  - Measure interaction delays

## Contributing
1. Follow the Feature-First architecture
2. Ensure type safety
3. Add tests for new functionality
4. Update documentation
5. Follow the existing code style 