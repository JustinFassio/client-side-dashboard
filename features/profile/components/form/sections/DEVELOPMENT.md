# Profile Section Development Guide

This guide provides detailed instructions for both migrating existing profile sections from the legacy `class-profile-endpoints.php` to the new modular structure, and creating new sections from scratch.

## Migrating Existing Sections

### 1. Identify Legacy Code
First, locate your section's code in `class-profile-endpoints.php`:
```php
// Example of legacy endpoint
public function update_account_info( WP_REST_Request $request ) {
    $user_id = get_current_user_id();
    $data = $request->get_params();
    // ... validation and update logic
}
```

### 2. Create Service Methods
1. Move business logic to `Profile_Service.php`:
```php
public function update_user_data( int $user_id, array $data ): array|WP_Error {
    // Validation
    $validation_result = $this->validator->validate_user_data( $data );
    if ( is_wp_error( $validation_result ) ) {
        return $validation_result;
    }

    // Update logic
    $user_data = array(
        'ID' => $user_id,
        // ... mapped fields
    );

    // Error handling
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( 'Profile Service: Processing update' );
    }

    return $result;
}
```

### 3. Create React Component
1. Create new file in `sections/` directory:
```typescript
// AccountSection.tsx
import React, { useState, useEffect } from 'react';
import { ProfileService } from '@/services';

interface Props {
    userData: UserData;
    onUpdate: (data: UpdateData) => void;
    onError: (error: Error) => void;
}

export const AccountSection: React.FC<Props> = ({ userData, onUpdate, onError }) => {
    // State management
    const [formData, setFormData] = useState({});
    const [originalData, setOriginalData] = useState({});

    // Validation and update logic
    const handleSubmit = async () => {
        try {
            // Validation
            // API call
            // Success handling
        } catch (error) {
            onError(error);
        }
    };

    return (
        <div className="section">
            {/* Form fields */}
        </div>
    );
};
```

### 4. Update Validation
1. Add validation rules to `Profile_Validator.php`:
```php
public function validate_user_data( array $data ): bool|WP_Error {
    // Field validation
    if ( isset( $data['email'] ) ) {
        $validation = $this->validate_email( $data['email'] );
        if ( is_wp_error( $validation ) ) {
            return $validation;
        }
    }
    return true;
}
```

### 5. Update Tests
1. Migrate/create PHP unit tests
2. Add React component tests
3. Add integration tests

---

## Creating New Sections

### 1. Plan Your Section
1. Define the data structure:
```typescript
interface SectionData {
    field1: string;
    field2: number;
    // ... other fields
}
```

2. Define validation rules
3. Plan API endpoints

### 2. Create Service Methods
1. Add to `Profile_Service.php`:
```php
public function get_section_data( int $user_id ): array|WP_Error {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( sprintf( 'Profile Service: Fetching section data for user %d', $user_id ) );
    }

    try {
        // Fetch logic
        return $data;
    } catch ( \Exception $e ) {
        return new WP_Error(
            'section_error',
            __( 'Failed to fetch section data', 'athlete-dashboard' )
        );
    }
}
```

### 3. Create React Component
1. Create new file: `sections/NewSection.tsx`
```typescript
import React from 'react';
import { useForm } from '@/hooks';
import { ProfileService } from '@/services';

export const NewSection: React.FC<SectionProps> = (props) => {
    // Implementation
};
```

2. Add to main form:
```typescript
// ProfileForm.tsx
import { NewSection } from './sections';

// Add to form sections
<NewSection
    data={sectionData}
    onUpdate={handleSectionUpdate}
/>
```

### 4. Implement Validation
1. Add to `Profile_Validator.php`:
```php
public function validate_section_data( array $data ): bool|WP_Error {
    $required_fields = array( 'field1', 'field2' );
    foreach ( $required_fields as $field ) {
        if ( empty( $data[$field] ) ) {
            return new WP_Error(
                'validation_error',
                sprintf( __( '%s is required', 'athlete-dashboard' ), $field )
            );
        }
    }
    return true;
}
```

### 5. Add Tests
1. Create `__tests__/NewSection.test.tsx`:
```typescript
import { render, fireEvent } from '@testing-library/react';
import { NewSection } from '../NewSection';

describe('NewSection', () => {
    it('renders correctly', () => {
        // Test implementation
    });

    it('handles updates', async () => {
        // Test implementation
    });
});
```

### 6. Update Documentation
1. Add section to `README.md`
2. Document API endpoints
3. Add usage examples

## Best Practices

### State Management
- Use React hooks for local state
- Consider Redux for complex state
- Maintain original data for change detection

### Error Handling
```typescript
try {
    const result = await ProfileService.updateSection(data);
    handleSuccess(result);
} catch (error) {
    if (error instanceof ValidationError) {
        setFieldErrors(error.fields);
    } else {
        onError(error);
    }
}
```

### Performance
- Implement `React.memo()` for expensive renders
- Use `useCallback` for handlers
- Batch state updates

### Security
- Sanitize all inputs
- Validate on both client and server
- Use WordPress nonces
- Implement proper capability checks

## Common Pitfalls
1. **Forgetting Original Data**
   - Always store original values for change detection
   - Implement proper dirty checking

2. **Validation Gaps**
   - Validate both client and server-side
   - Handle all edge cases

3. **Error Handling**
   - Implement comprehensive error handling
   - Provide user-friendly messages
   - Log errors appropriately

4. **Performance**
   - Avoid unnecessary re-renders
   - Implement proper memoization
   - Use appropriate caching strategies 