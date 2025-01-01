# Profile Feature

## Overview
The Profile feature manages user profile information in the Athlete Dashboard. It provides a modular, type-safe implementation for collecting, validating, and storing user data including basic information, physical metrics, and medical details.

## Architecture

### Directory Structure
```plaintext
features/profile/
├── components/
│   ├── form/
│   │   ├── fields/         # Reusable form field components
│   │   ├── sections/       # Form section components
│   │   └── ProfileForm.tsx # Main form container
│   └── SaveAlert.tsx       # Success/error notification component
├── services/
│   ├── ProfileService.ts   # Profile data management and API integration
│   └── ValidationService.ts # Form validation logic
├── types/
│   ├── profile.ts          # Core type definitions
│   └── validation.ts       # Validation-specific types
├── events/
│   ├── events.ts           # Event definitions and utilities
│   └── __tests__/         # Event tests
└── __tests__/             # Feature tests
```

### Key Components

#### ProfileForm
- Main container component managing form state and section navigation
- Implements real-time validation
- Handles data persistence and error states
- Uses a tabbed interface for section organization

#### Form Sections
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

### Services

#### ProfileService
- Manages API interactions
- Implements caching for performance
- Handles data normalization
- Emits events for state changes

#### ValidationService
- Provides field-level validation
- Implements cross-field validation rules
- Supports custom validation rules

### Event System
The feature uses a strongly-typed event system for state management:

```typescript
// Event example
Events.emit(PROFILE_EVENTS.UPDATE_REQUEST, {
    type: PROFILE_EVENTS.UPDATE_REQUEST,
    payload: profileData
});
```

Available events:
- `FETCH_REQUEST/SUCCESS/ERROR`: Profile data loading
- `UPDATE_REQUEST/SUCCESS/ERROR`: Profile updates
- `SECTION_CHANGE`: Form navigation
- `VALIDATION_ERROR`: Validation state
- `FORM_RESET`: Form state reset

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

### Handling Profile Updates
```typescript
import { Events } from '../../../dashboard/core/events';
import { PROFILE_EVENTS } from './events/events';

// Listen for profile updates
Events.on(PROFILE_EVENTS.UPDATE_SUCCESS, (event) => {
    console.log('Profile updated:', event.payload);
});
```

## Data Flow

1. **Initial Load**
   ```
   ProfileForm -> FETCH_REQUEST -> ProfileService
                  FETCH_SUCCESS -> Update UI
   ```

2. **Form Updates**
   ```
   Field Change -> Validation -> Update State
                   Emit Events -> Update UI
   ```

3. **Save Flow**
   ```
   Submit -> Validate -> UPDATE_REQUEST
                     -> UPDATE_SUCCESS/ERROR
                     -> Update UI
   ```

## Development

### Adding New Fields
1. Add field type to `ProfileData` interface
2. Add validation rules in `ValidationService`
3. Update relevant form section
4. Add event handling if needed

### Testing
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
- [ ] Add field-level loading states
- [ ] Implement optimistic updates
- [ ] Add form state persistence
- [ ] Enhance accessibility
- [ ] Add analytics tracking

## Contributing
1. Follow the Feature-First architecture
2. Ensure type safety
3. Add tests for new functionality
4. Update documentation
5. Follow the existing code style 