# Profile Feature

## Overview
The Profile feature manages athlete profile data, including personal information, preferences, and training settings. It provides a complete interface for users to view and update their profile information while maintaining data consistency across the application.

## Debug Logging

### Implementation
The Profile feature now uses the core `Debug` class for all logging operations:

```php
use AthleteDashboard\Core\Config\Debug;

// Example logging patterns
Debug::log( sprintf( 'Operation [user_id=%d]', $user_id ), 'profile' );
Debug::log( sprintf( 'Data update [user_id=%d, fields=%d]', $user_id, $count ), 'profile' );
Debug::log( sprintf( 'Operation failed: %s [user_id=%d]', $error, $user_id ), 'profile' );
```

### Logging Patterns
- Operation Start: `Operation [user_id=%d]`
- Data State: `Data state [user_id=%d, fields=%s]`
- Success: `Operation complete [user_id=%d, updated=%d]`
- Errors: `Operation failed: %s [user_id=%d]`

### Context
All logs use the 'profile' context string for consistent filtering and include:
- User ID for all operations
- Field counts for data operations
- Error messages for failures
- Operation-specific metrics

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

### Get User Profile

```
GET /wp-json/athlete-dashboard/v1/profile/user/{id}
```

Retrieves a user's profile data.

#### Parameters

- `id` (number, required): The ID of the user whose profile to retrieve.

#### Response

```json
{
  "user_id": 1,
  "data": {
    "basic": {
      "firstName": "string",
      "lastName": "string",
      "email": "string"
    },
    "medical": {
      "medicalConditions": ["string"],
      "exerciseLimitations": ["string"],
      "notes": "string"
    },
    "account": {
      "username": "string",
      "displayName": "string"
    },
    "injuries": [
      {
        "id": "string",
        "type": "string",
        "description": "string",
        "date": "string"
      }
    ]
  }
}
```

#### Error Responses

- `401 Unauthorized`: User is not logged in
- `403 Forbidden`: User does not have permission to view the requested profile
- `404 Not Found`: Profile not found

### Update User Profile

```
POST /wp-json/athlete-dashboard/v1/profile/user/{id}
```

Updates a user's profile data.

#### Parameters

- `id` (number, required): The ID of the user whose profile to update.
- `data` (object, required): The profile data to update.

#### Request Body

```json
{
  "basic": {
    "firstName": "string",
    "lastName": "string",
    "email": "string"
  },
  "medical": {
    "medicalConditions": ["string"],
    "exerciseLimitations": ["string"],
    "notes": "string"
  },
  "account": {
    "username": "string",
    "displayName": "string"
  },
  "injuries": [
    {
      "id": "string",
      "type": "string",
      "description": "string",
      "date": "string"
    }
  ]
}
```

#### Response

Returns the updated profile data in the same format as the Get User Profile endpoint.

#### Error Responses

- `401 Unauthorized`: User is not logged in
- `403 Forbidden`: User does not have permission to update the profile
- `404 Not Found`: Profile not found
- `400 Bad Request`: Invalid request data

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

### Environment
We use jsdom to simulate a DOM environment for React Testing Library. This allows us to test React components in a browser-like environment without a real browser.

### Test Organization
All Profile tests, including UI and hooks, are under `features/profile/tests/`. This follows our "feature-first" pattern where all test files related to a feature are co-located with the feature code.

Structure:
```
features/profile/
├── tests/
│   ├── Unit/           # Unit tests for utilities and hooks
│   ├── Integration/    # Integration tests
│   └── Components/     # React component tests
```

### Running Tests
```bash
# Run profile feature tests
npm run test features/profile

# Run specific test file
npm run test features/profile/tests/Unit/Components/ProfileForm.test.tsx
```

### Test Patterns
- Use React Testing Library for component tests
- Follow AAA pattern (Arrange, Act, Assert)
- Mock external dependencies
- Test error states and loading states
- Validate form submissions and user interactions

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
- Phase 1 (Complete): Documentation and deprecation notices
- Phase 2 (Complete): Feature parity and testing
- Phase 3 (Current): Frontend migration
- Phase 4: Legacy code removal

## Recent Changes

### Debug Logging Standardization
- Replaced custom logging methods with core `Debug` class
- Implemented consistent logging patterns across all endpoints
- Added structured context to all log messages
- Improved error tracking and debugging capabilities

### Code Quality Improvements
- Standardized error handling
- Enhanced logging coverage
- Improved code documentation
- Removed deprecated logging methods

## Support
For any issues or questions during migration, please:
1. Check the debug logs for deprecated endpoint usage
2. Review the new implementation in `features/profile/api/`
3. Test endpoints using the provided test routes
4. Contact the development team for assistance 

## Refactoring Plan

The Profile feature is undergoing a gradual refactoring to improve maintainability and testability. The plan is broken down into small, safe increments:

### Phase 1: Initial Safe Separation
- Move ProfileEndpoints.php into the api/ directory
- Ensure all endpoints remain functional
- Update any necessary imports/requires

### Phase 2: Extract Validation
- Create new ProfileValidator class in validation/ directory
- Move validation logic while maintaining backward compatibility
- Test both implementations in parallel
- Remove old validation once confirmed working

### Current Status
- Phase 1 in progress
- Original functionality maintained during transition
- Each phase will be implemented via separate PRs for safe review

### Testing Strategy
- Maintain existing endpoint functionality throughout
- Run full test suite after each change
- Manual verification of critical endpoints
- Document any breaking changes or deprecations 

## Refactoring Progress

### Phase 1 Completion Report
**Date**: January 13, 2025

#### Summary
Successfully completed the file organization and naming standardization phase of the Profile feature refactoring.

#### Completed Changes

1. **File Renaming**
   - ✅ Renamed main file to `class-profile-endpoints.php`
   - ✅ Verified file location at `features/profile/api/class-profile-endpoints.php`
   - ✅ Confirmed no duplicate files exist

2. **File Loading**
   - ✅ Verified require statement in functions.php:
     ```php
     require_once get_stylesheet_directory() . '/features/profile/api/class-profile-endpoints.php';
     ```
   - ✅ Confirmed proper loading sequence with other profile-related files:
     - Config/Config.php
     - api/class-profile-endpoints.php
     - rest-api/class-profile-controller.php
     - admin/user-profile.php

3. **Namespace Verification**
   - ✅ Confirmed correct namespace: `AthleteDashboard\Features\Profile\API`
   - ✅ Follows WordPress-PHP naming standards
   - ✅ Properly reflects feature directory structure

4. **Functionality Testing**
   - ✅ All endpoints responding correctly:
     - `/wp-json/athlete-dashboard/v1/profile/public-test` (200 OK)
     - `/wp-json/athlete-dashboard/v1/profile` (401 Unauthorized - Expected)
     - `/wp-json/athlete-dashboard/v1/profile/basic` (401 Unauthorized - Expected)
     - `/wp-json/athlete-dashboard/v1/profile/full` (401 Unauthorized - Expected)
   - ✅ Authentication checks working as intended
   - ✅ No PHP syntax errors
   - ✅ Class loading and initialization verified

#### Known Issues for Future Phases

1. **Deprecation Notice**
   - ⚠️ Warning about `athlete_dashboard_register_rest_routes`
   - Scheduled for addressing in Phase 2
   - Current functionality not impacted

2. **Code Organization**
   - ⚠️ Removed redundant initialization call from class file
   - Main initialization now handled properly in functions.php

#### Verification Steps
1. Pull latest changes from version control
2. Run PHP linting check:
   ```bash
   php -l features/profile/api/class-profile-endpoints.php
   ```
3. Verify endpoint responses using curl commands:
   ```bash
   curl -i http://aiworkoutgenerator-local.local/wp-json/athlete-dashboard/v1/profile/public-test
   curl -i http://aiworkoutgenerator-local.local/wp-json/athlete-dashboard/v1/profile
   curl -i http://aiworkoutgenerator-local.local/wp-json/athlete-dashboard/v1/profile/basic
   curl -i http://aiworkoutgenerator-local.local/wp-json/athlete-dashboard/v1/profile/full
   ```
4. Confirm no PHP errors in WordPress debug log

#### Next Steps
1. **Phase 2 Planning**
   - Extract validation logic
   - Create service layer
   - Implement repositories

2. **Documentation Updates**
   - Update API documentation to reflect new file structure
   - Document deprecated method for future refactoring

#### Additional Notes
- No breaking changes introduced
- All existing functionality preserved
- File naming now follows WordPress coding standards
- Directory structure remains consistent with Feature-First architecture 

### Phase 2 Planning Document
**Date**: January 13, 2025

#### Overview
Focus on extracting and organizing business logic into appropriate layers while maintaining the Feature-First architecture.

#### Key Objectives
1. **Extract Validation Logic**
   - Move validation from endpoints to dedicated validators
   - Implement consistent validation patterns
   - Maintain WordPress coding standards

2. **Create Service Layer**
   - Abstract business logic from controllers
   - Implement proper separation of concerns
   - Prepare for future repository pattern integration

3. **Implement Repository Pattern**
   - Abstract data access logic
   - Standardize WordPress meta operations
   - Prepare for potential future data source changes

#### Technical Design

1. **Validation Layer**
```php
namespace AthleteDashboard\Features\Profile\Validation;

class Profile_Validator {
    /**
     * Validate profile data
     *
     * @param array $data The profile data to validate
     * @return array|\WP_Error Validated data or WP_Error on failure
     */
    public function validate_profile_data(array $data): array|\WP_Error {
        // Implementation details to be determined
    }
}
```

2. **Service Layer**
```php
namespace AthleteDashboard\Features\Profile\Service;

class Profile_Service {
    private $validator;
    private $repository;

    public function __construct(
        Profile_Validator $validator,
        Profile_Repository $repository
    ) {
        $this->validator = $validator;
        $this->repository = $repository;
    }

    /**
     * Update user profile
     *
     * @param int   $user_id User ID
     * @param array $data    Profile data
     * @return array|\WP_Error Updated profile or WP_Error on failure
     */
    public function update_profile(int $user_id, array $data): array|\WP_Error {
        // Implementation details to be determined
    }
}
```

3. **Repository Layer**
```php
namespace AthleteDashboard\Features\Profile\Repository;

class Profile_Repository {
    /**
     * Get user profile data
     *
     * @param int $user_id User ID
     * @return array Profile data
     */
    public function get_profile(int $user_id): array {
        // Implementation details to be determined
    }
}
```

#### Directory Structure
```plaintext
features/profile/
├── api/
│   └── class-profile-endpoints.php
├── validation/
│   └── class-profile-validator.php
├── service/
│   └── class-profile-service.php
├── repository/
│   └── class-profile-repository.php
└── README.md
```

#### Implementation Plan

1. **Extract Validation (2-3 days)**
   - Create validation/ directory
   - Implement Profile_Validator class
   - Move existing validation logic from endpoints
   - Update endpoint references

2. **Create Service Layer (2-3 days)**
   - Create service/ directory
   - Implement Profile_Service class
   - Move business logic from endpoints
   - Inject dependencies (validator, repository)

3. **Implement Repository (2-3 days)**
   - Create repository/ directory
   - Implement Profile_Repository class
   - Move all WordPress meta operations
   - Update service to use repository

4. **Testing & Documentation (2-3 days)**
   - Unit tests for each layer
   - Integration tests
   - Update documentation

#### Testing Strategy

1. **Unit Tests**
   - Validator: Test all validation rules, error messages, edge cases
   - Service: Mock dependencies, test business logic, verify error handling
   - Repository: Mock WordPress functions, test meta operations

2. **Integration Tests**
   - End-to-end profile updates
   - Validation error handling
   - WordPress meta operations

#### Risk Mitigation
1. Implement changes incrementally
2. Maintain backward compatibility
3. Add comprehensive tests before changes
4. Document all breaking changes

#### Success Criteria
1. All logic properly separated into layers
2. Unit tests passing
3. No direct WordPress meta access in endpoints
4. Consistent error handling
5. Documentation updated

#### Dependencies
1. WordPress coding standards
2. PHPUnit for testing
3. Existing profile feature codebase
4. Development environment setup

#### Open Questions
1. Should we implement a specific validation library?
2. Do we need caching at the repository level?
3. How should we handle backward compatibility?
4. What level of test coverage is required? 

## Database Configuration

### Profile Tables
The profile feature uses the following database tables:
- `wp_athlete_profiles`: Stores core profile data
- `wp_athlete_physical_data`: Stores physical measurements
- `wp_athlete_physical_history`: Stores historical physical measurements

### Test Database Setup
When running profile feature tests:

1. Ensure the test database is configured in `wp-tests-config.php`:
```php
define( 'DB_NAME', 'wordpress_test' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', 'root' );
define( 'DB_HOST', 'localhost:/Users/justinfassio/Library/Application Support/Local/run/8U-IQPW3o/mysql/mysqld.sock' );
```

2. The test suite will automatically:
- Create necessary tables
- Set up test data
- Clean up after tests complete

### Running Profile Tests
To run profile-specific tests:
```bash
WP_TESTS_DIR=/tmp/wordpress-tests-lib vendor/bin/phpunit --filter=Profile
```

### Database Migrations
Profile feature includes database migrations that:
1. Create required tables
2. Add necessary indexes
3. Set up foreign key constraints

To run migrations manually:
```bash
wp athlete-dashboard migrate profile
```