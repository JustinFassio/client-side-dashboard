# Medical Section Migration Guide

## Key Lessons From Previous Migrations
1. **Keep WordPress Patterns**
   - Use `WP_Error` for error handling, not exceptions
   - Maintain WordPress's data flow patterns
   - Use WordPress's built-in sanitization functions

2. **Preserve Existing Data**
   - Always maintain original medical history
   - Don't overwrite existing conditions unless explicitly changed
   - Use proper data merging for complex medical data

## Migration Steps

### 1. Data Structure Analysis
```typescript
interface MedicalData {
    medicalConditions: string[];
    exerciseLimitations: string[];
    medications: string[];
    injuries?: {
        type: string;
        date: string;
        status: 'active' | 'recovered';
        notes?: string;
    }[];
    emergencyContact?: {
        name: string;
        relationship: string;
        phone: string;
    };
    preferences: {
        shareWithTrainer: boolean;
        notifyOnEmergency: boolean;
    };
}

// Validation types
interface MedicalValidation {
    fieldErrors?: {
        medicalConditions?: string[];
        exerciseLimitations?: string[];
        medications?: string[];
        injuries?: string[];
    };
    generalErrors?: string[];
}
```

### 2. Service Layer Implementation
```php
// Profile_Service.php

/**
 * Get medical profile data.
 *
 * @param int $user_id User ID.
 * @return array|WP_Error Medical data or error.
 */
public function get_medical_data( int $user_id ): array|WP_Error {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( sprintf( 'Profile Service: Fetching medical data for user %d', $user_id ) );
    }

    // Get data from repository
    $data = $this->repository->get_medical_data( $user_id );
    if ( is_wp_error( $data ) ) {
        return $data;
    }

    // Get user preferences
    $preferences = get_user_meta( $user_id, 'medical_preferences', true ) ?: array(
        'shareWithTrainer' => false,
        'notifyOnEmergency' => true
    );

    // Get emergency contact
    $emergency_contact = get_user_meta( $user_id, 'emergency_contact', true );

    return array_merge( $data, array(
        'preferences' => $preferences,
        'emergencyContact' => $emergency_contact
    ) );
}

/**
 * Update medical profile data.
 *
 * @param int   $user_id User ID.
 * @param array $data    Medical data to update.
 * @return array|WP_Error Updated data or error.
 */
public function update_medical_data( int $user_id, array $data ): array|WP_Error {
    // Validation
    $validation_result = $this->validator->validate_medical_data( $data );
    if ( is_wp_error( $validation_result ) ) {
        return $validation_result;
    }

    // Sanitize arrays
    $data['medicalConditions'] = array_map( 'sanitize_text_field', $data['medicalConditions'] );
    $data['exerciseLimitations'] = array_map( 'sanitize_text_field', $data['exerciseLimitations'] );
    $data['medications'] = array_map( 'sanitize_text_field', $data['medications'] );

    // Update main data
    $update_result = $this->repository->update_medical_data( $user_id, $data );
    if ( is_wp_error( $update_result ) ) {
        return $update_result;
    }

    // Update preferences if provided
    if ( isset( $data['preferences'] ) ) {
        update_user_meta( $user_id, 'medical_preferences', $data['preferences'] );
    }

    // Update emergency contact if provided
    if ( isset( $data['emergencyContact'] ) ) {
        update_user_meta( $user_id, 'emergency_contact', $data['emergencyContact'] );
    }

    return $this->get_medical_data( $user_id );
}
```

### 3. React Component Implementation
```typescript
// MedicalSection.tsx
import React from 'react';
import { FormField } from '../fields';
import { MedicalData, MedicalValidation } from '../../../types';

interface Props {
    data: MedicalData;
    onUpdate: (data: Partial<MedicalData>) => void;
    onError: (error: Error) => void;
    validation?: MedicalValidation;
}

const MEDICAL_CONDITIONS = [
    { value: 'none', label: 'None' },
    { value: 'heart_condition', label: 'Heart Condition' },
    { value: 'asthma', label: 'Asthma' },
    { value: 'diabetes', label: 'Diabetes' },
    { value: 'hypertension', label: 'Hypertension' },
    { value: 'other', label: 'Other' }
];

const EXERCISE_LIMITATIONS = [
    { value: 'none', label: 'None' },
    { value: 'joint_pain', label: 'Joint Pain' },
    { value: 'back_pain', label: 'Back Pain' },
    { value: 'limited_mobility', label: 'Limited Mobility' },
    { value: 'balance_issues', label: 'Balance Issues' },
    { value: 'other', label: 'Other' }
];

export const MedicalSection: React.FC<Props> = ({
    data,
    onUpdate,
    onError,
    validation
}) => {
    const handleChange = (name: string, value: any) => {
        try {
            onUpdate({ [name]: value });
        } catch (error) {
            onError(error as Error);
        }
    };

    return (
        <div className="medical-section">
            <FormField
                name="medicalConditions"
                label="Medical Conditions"
                type="select"
                value={data.medicalConditions}
                onChange={handleChange}
                options={MEDICAL_CONDITIONS}
                isArray={true}
                validation={validation?.fieldErrors?.medicalConditions}
                required
            />
            {/* Additional fields */}
        </div>
    );
};
```

### 4. Enhanced Validation Implementation
```php
public function validate_medical_data( array $data ): bool|WP_Error {
    // Required fields validation
    if ( empty( $data['medicalConditions'] ) || ! is_array( $data['medicalConditions'] ) ) {
        return new WP_Error(
            'validation_error',
            __( 'Medical conditions must be specified', 'athlete-dashboard' )
        );
    }

    // Validate allowed values
    $valid_conditions = array( 'none', 'heart_condition', 'asthma', 'diabetes', 'hypertension', 'other' );
    foreach ( $data['medicalConditions'] as $condition ) {
        if ( ! in_array( $condition, $valid_conditions, true ) ) {
            return new WP_Error(
                'validation_error',
                sprintf( __( 'Invalid medical condition: %s', 'athlete-dashboard' ), $condition )
            );
        }
    }

    // Validate emergency contact if provided
    if ( ! empty( $data['emergencyContact'] ) ) {
        if ( empty( $data['emergencyContact']['phone'] ) ) {
            return new WP_Error(
                'validation_error',
                __( 'Emergency contact phone is required', 'athlete-dashboard' )
            );
        }
    }

    return true;
}
```

## Testing Strategy

### 1. Unit Tests
```typescript
// __tests__/MedicalSection.test.tsx
describe('MedicalSection', () => {
    it('handles multiple condition selection', () => {
        const { getByLabelText } = render(<MedicalSection {...mockProps} />);
        
        const asthmaOption = getByLabelText('Asthma');
        const diabetesOption = getByLabelText('Diabetes');
        
        fireEvent.click(asthmaOption);
        fireEvent.click(diabetesOption);
        
        expect(mockUpdateFn).toHaveBeenCalledWith({
            medicalConditions: ['asthma', 'diabetes']
        });
    });

    it('validates required fields', () => {
        // Test implementation
    });
});
```

### 2. Integration Tests
```php
class Medical_Profile_Test extends WP_UnitTestCase {
    public function test_medical_data_update() {
        $user_id = $this->factory->user->create();
        
        $data = array(
            'medicalConditions' => array( 'asthma', 'diabetes' ),
            'exerciseLimitations' => array( 'joint_pain' ),
            'medications' => array( 'insulin' )
        );
        
        $result = $this->service->update_medical_data( $user_id, $data );
        
        $this->assertNotWPError( $result );
        $this->assertEquals( $data['medicalConditions'], $result['medicalConditions'] );
    }
}
```

## Data Migration Guide

### Database Schema
```sql
-- Legacy structure (user meta)
-- wp_usermeta
-- meta_key: 'medical_conditions', meta_value: 'a:2:{i:0;s:6:"asthma";i:1;s:8:"diabetes"}'
-- meta_key: 'medications', meta_value: 'a:1:{i:0;s:7:"insulin"}'

-- New structure (medical_records table)
CREATE TABLE `wp_medical_records` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) unsigned NOT NULL,
    `conditions` json NOT NULL,
    `limitations` json,
    `medications` json,
    `updated_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### WP-CLI Migration Command
```php
/**
 * Migrate medical data for all users
 */
public function migrate_all_medical_data() {
    $users = get_users( array( 'fields' => array( 'ID' ) ) );
    $progress = \WP_CLI\Utils\make_progress_bar(
        'Migrating medical data',
        count( $users )
    );

    foreach ( $users as $user ) {
        $result = $this->migrate_legacy_medical_data( $user->ID );
        if ( is_wp_error( $result ) ) {
            \WP_CLI::warning(
                sprintf(
                    'Failed to migrate medical data for user %d: %s',
                    $user->ID,
                    $result->get_error_message()
                )
            );
        }
        $progress->tick();
    }

    $progress->finish();
}
```

## FAQ

### Common Questions

1. **Q: How is sensitive medical data handled?**
   - A: Medical data is stored in a separate table with restricted access. Only authorized roles can view complete medical history.

2. **Q: What happens when a user has no medical conditions?**
   - A: The 'none' option should be selected, and other fields become optional.

3. **Q: How are emergency contacts managed?**
   - A: Emergency contacts are stored as user meta for quick access and are encrypted when containing sensitive information.

4. **Q: How do we handle historical medical data?**
   - A: Changes to medical conditions are logged with timestamps, maintaining a history of changes.

## Migration Checklist
- [ ] Review existing medical data structure
- [ ] Implement service layer methods with proper sanitization
- [ ] Create React component with proper validation
- [ ] Add comprehensive error handling
- [ ] Write unit and integration tests
- [ ] Update documentation
- [ ] Verify data privacy compliance
- [ ] Test all edge cases
- [ ] Deploy incrementally with monitoring 