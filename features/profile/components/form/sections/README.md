# Profile Form Sections

This directory contains the different sections of the profile form, each handling specific aspects of user data. The sections are designed to be modular and independently maintainable.

## Account Section

### Overview
The `AccountSection` handles core user account information and implements a robust validation and update workflow. This section manages critical user data such as email, username, and display name.

### Data Flow
1. **Initial Load**
   - Fetches user data via `ProfileService.get_user_data()`
   - Populates form with existing user information
   - Maintains original email for change detection

2. **Update Process**
   ```mermaid
   graph TD
   A[User Updates Form] --> B[Form Validation]
   B --> C{Email Changed?}
   C -->|Yes| D[Email Validation]
   C -->|No| E[Other Field Updates]
   D --> F[Backend Validation]
   E --> F
   F --> G[Update Profile]
   G --> H[Success/Error Handling]
   ```

### Endpoints Used
- **GET User Data**: `ProfileService.get_user_data()`
  - Returns: `{ id, username, email, roles, firstName, lastName, displayName, nickname }`
  - Error Handling: Returns `WP_Error` on failure

- **UPDATE User Data**: `ProfileService.update_user_data()`
  - Payload: `{ firstName?, lastName?, displayName?, email?, nickname? }`
  - Validation: Checks email format, uniqueness
  - Error Handling: Returns detailed error messages for specific failures

### Validation Rules
1. **Email**
   - Must be valid format
   - Must be unique in system
   - Only validated if changed from original
   - Preserves existing email if field is empty

2. **Display Name**
   - Required field
   - Trimmed of whitespace
   - Sanitized for security

### Error Handling
- Form-level validation errors
- Backend validation failures
- Network/API errors
- User-friendly error messages
- Debug logging (when WP_DEBUG enabled)

### State Management
```typescript
interface AccountState {
    email: string;
    originalEmail: string;  // For change detection
    displayName: string;
    // ... other fields
}
```

### Usage Example
```typescript
<AccountSection
    userData={userData}
    onUpdate={handleUpdate}
    onError={handleError}
/>
```

---

## Physical Section (To Be Updated)
The Physical section handles user physical attributes and measurements.
*Documentation pending new implementation*

### Current Features
- Basic physical measurements
- Unit preferences
- Historical tracking

---

## Basic Section (To Be Updated)
The Basic section manages fundamental user information.
*Documentation pending new implementation*

### Current Features
- Name fields
- Contact preferences
- Basic demographics

---

## Medical Section (To Be Updated)
The Medical section handles health-related information.
*Documentation pending new implementation*

### Current Features
- Medical history
- Current conditions
- Emergency contacts

---

## Development Guidelines

### Adding New Sections
1. Create new section component in this directory
2. Follow existing pattern for state management
3. Implement error handling
4. Add to main form component
5. Update this documentation

### Testing
- Each section should have comprehensive unit tests
- Test both success and error paths
- Validate all form interactions
- Test API integration points

### Error Handling Best Practices
1. Use consistent error message format
2. Implement proper validation
3. Handle API errors gracefully
4. Provide user feedback
5. Log errors appropriately

### Performance Considerations
- Implement proper memoization
- Optimize re-renders
- Use efficient form state management
- Consider code splitting if sections grow large 