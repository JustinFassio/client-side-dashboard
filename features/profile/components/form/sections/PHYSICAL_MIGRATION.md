# Physical Section Migration Guide

## Key Lessons From Account Section Migration
1. **Keep WordPress Patterns**
   - Use `WP_Error` for error handling, not exceptions
   - Maintain WordPress's data flow patterns
   - Use WordPress's built-in sanitization functions

2. **Preserve Existing Data**
   - Always maintain original data for change detection
   - Don't overwrite fields that aren't being updated
   - Use proper data merging strategies

## Migration Steps

### 1. Data Structure Analysis
```typescript
interface PhysicalData {
    height: number;
    weight: number;
    units: {
        height: 'cm' | 'ft';
        weight: 'kg' | 'lbs';
    };
    measurements?: {
        chest?: number;
        waist?: number;
        hips?: number;
        // ... other measurements
    };
    preferences: {
        showMetric: boolean;
        trackHistory: boolean;
    };
}
```

### 2. Service Layer Implementation
```php
// Profile_Service.php

/**
 * Get physical profile data.
 *
 * @param int $user_id User ID.
 * @return array|WP_Error Physical data or error.
 */
public function get_physical_data( int $user_id ): array|WP_Error {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( sprintf( 'Profile Service: Fetching physical data for user %d', $user_id ) );
    }

    // Get data from repository
    $data = $this->repository->get_physical_data( $user_id );
    if ( is_wp_error( $data ) ) {
        return $data;
    }

    // Get user preferences
    $preferences = get_user_meta( $user_id, 'physical_preferences', true ) ?: array(
        'showMetric' => false,
        'trackHistory' => true
    );

    return array_merge( $data, array( 'preferences' => $preferences ) );
}

/**
 * Update physical profile data.
 *
 * @param int   $user_id User ID.
 * @param array $data    Physical data to update.
 * @return array|WP_Error Updated data or error.
 */
public function update_physical_data( int $user_id, array $data ): array|WP_Error {
    // Validation
    $validation_result = $this->validator->validate_physical_data( $data );
    if ( is_wp_error( $validation_result ) ) {
        return $validation_result;
    }

    // Update main data
    $update_result = $this->repository->update_physical_data( $user_id, $data );
    if ( is_wp_error( $update_result ) ) {
        return $update_result;
    }

    // Update preferences if provided
    if ( isset( $data['preferences'] ) ) {
        update_user_meta( $user_id, 'physical_preferences', $data['preferences'] );
    }

    return $this->get_physical_data( $user_id );
}

#### Legacy Data Migration
```php
/**
 * Migrate legacy physical data to new structure.
 *
 * @param int $user_id User ID.
 * @return array|WP_Error Migrated data or error.
 */
private function migrate_legacy_physical_data( int $user_id ): array|WP_Error {
    // Check user exists
    if ( ! get_userdata( $user_id ) ) {
        return new WP_Error(
            'invalid_user',
            __( 'User does not exist', 'athlete-dashboard' )
        );
    }

    // Get legacy data
    $legacy_height = get_user_meta( $user_id, 'user_height', true );
    $legacy_weight = get_user_meta( $user_id, 'user_weight', true );
    $legacy_units = get_user_meta( $user_id, 'measurement_units', true );

    // Convert to new format
    $physical_data = array(
        'height' => $legacy_height ? (float) $legacy_height : 0,
        'weight' => $legacy_weight ? (float) $legacy_weight : 0,
        'units' => array(
            'height' => $legacy_units === 'imperial' ? 'ft' : 'cm',
            'weight' => $legacy_units === 'imperial' ? 'lbs' : 'kg'
        )
    );

    // Validate converted data
    $validation_result = $this->validator->validate_physical_data( $physical_data );
    if ( is_wp_error( $validation_result ) ) {
        return $validation_result;
    }

    // Store in new format
    return $this->update_physical_data( $user_id, $physical_data );
}
```

### 3. React Component Implementation

#### Dynamic Measurement Fields
```typescript
// PhysicalSection.tsx

interface MeasurementField {
    key: string;
    label: string;
    unit: string;
    min: number;
    max: number;
}

const MEASUREMENT_FIELDS: MeasurementField[] = [
    { key: 'chest', label: 'Chest', unit: 'cm', min: 30, max: 200 },
    { key: 'waist', label: 'Waist', unit: 'cm', min: 30, max: 200 },
    { key: 'hips', label: 'Hips', unit: 'cm', min: 30, max: 200 }
];

const MeasurementFields: React.FC<{ data: PhysicalData }> = ({ data }) => {
    return (
        <div className="measurements-grid">
            {MEASUREMENT_FIELDS.map(field => (
                <div key={field.key} className="measurement-field">
                    <label htmlFor={field.key}>{field.label}</label>
                    <input
                        type="number"
                        id={field.key}
                        name={field.key}
                        min={field.min}
                        max={field.max}
                        value={data.measurements?.[field.key] ?? ''}
                        onChange={handleChange}
                    />
                    <span className="unit">{field.unit}</span>
                </div>
            ))}
        </div>
    );
};
```

### 4. Enhanced Validation Implementation
```php
public function validate_physical_data( array $data ): bool|WP_Error {
    // Check user exists
    if ( ! get_userdata( $data['user_id'] ) ) {
        return new WP_Error(
            'invalid_user',
            __( 'User does not exist', 'athlete-dashboard' )
        );
    }

    // Validate required fields
    $required = array( 'height', 'weight' );
    foreach ( $required as $field ) {
        if ( ! isset( $data[$field] ) || ! is_numeric( $data[$field] ) ) {
            return new WP_Error(
                'validation_error',
                sprintf( __( '%s is required and must be numeric', 'athlete-dashboard' ), $field )
            );
        }
    }

    // Validate units
    $valid_height_units = array( 'cm', 'ft' );
    $valid_weight_units = array( 'kg', 'lbs' );

    if ( ! in_array( $data['units']['height'], $valid_height_units, true ) ) {
        return new WP_Error(
            'validation_error',
            __( 'Unsupported height unit', 'athlete-dashboard' )
        );
    }

    if ( ! in_array( $data['units']['weight'], $valid_weight_units, true ) ) {
        return new WP_Error(
            'validation_error',
            __( 'Unsupported weight unit', 'athlete-dashboard' )
        );
    }

    // Validate ranges (in metric)
    $height = $data['height'];
    if ( $data['units']['height'] === 'ft' ) {
        $height = $height * 30.48; // Convert to cm
    }

    if ( $height <= 0 || $height > 300 ) {
        return new WP_Error(
            'validation_error',
            __( 'Height must be between 0 and 300 cm', 'athlete-dashboard' )
        );
    }

    return true;
}
```

## Testing Strategy

### 1. Unit Tests
```typescript
// __tests__/PhysicalSection.test.tsx
describe('PhysicalSection', () => {
    it('preserves existing data on partial updates', async () => {
        const initialData = {
            height: 180,
            weight: 75,
            units: { height: 'cm', weight: 'kg' }
        };
        
        const { getByLabelText } = render(
            <PhysicalSection data={initialData} />
        );
        
        // Update only weight
        const weightInput = getByLabelText('Weight');
        fireEvent.change(weightInput, { target: { value: '80' } });
        
        // Submit form
        fireEvent.click(getByLabelText('Save'));
        
        // Verify height remained unchanged
        expect(mockUpdateFn).toHaveBeenCalledWith(
            expect.objectContaining({
                height: 180,
                weight: 80
            })
        );
    });

    it('handles invalid input gracefully', () => {
        // Test implementation for invalid input
    });

    it('displays backend validation errors', async () => {
        // Test implementation for error display
    });
});
```

### 2. Integration Tests
```php
class Physical_Profile_Test extends WP_UnitTestCase {
    public function test_physical_data_partial_update() {
        $user_id = $this->factory->user->create();
        
        // Initial data
        $initial_data = array(
            'height' => 180,
            'weight' => 75,
            'units' => array(
                'height' => 'cm',
                'weight' => 'kg'
            )
        );
        
        $this->service->update_physical_data( $user_id, $initial_data );
        
        // Update only weight
        $update_data = array(
            'weight' => 80,
            'units' => array(
                'weight' => 'kg'
            )
        );
        
        $result = $this->service->update_physical_data( $user_id, $update_data );
        
        $this->assertNotWPError( $result );
        $this->assertEquals( 180, $result['height'] );
        $this->assertEquals( 80, $result['weight'] );
    }
}
```

## Data Migration Guide

### Database Schema
```sql
-- Legacy structure (user meta)
-- wp_usermeta
-- meta_key: 'user_height', meta_value: '180'
-- meta_key: 'user_weight', meta_value: '75'
-- meta_key: 'measurement_units', meta_value: 'metric'

-- New structure (physical_measurements table)
CREATE TABLE `wp_physical_measurements` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) unsigned NOT NULL,
    `height` float NOT NULL,
    `weight` float NOT NULL,
    `measurement_date` datetime NOT NULL,
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `measurement_date` (`measurement_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### WP-CLI Migration Command
```php
/**
 * Migrate physical data for all users
 */
public function migrate_all_users() {
    $users = get_users( array( 'fields' => array( 'ID' ) ) );
    $progress = \WP_CLI\Utils\make_progress_bar(
        'Migrating physical data',
        count( $users )
    );

    foreach ( $users as $user ) {
        $result = $this->migrate_legacy_physical_data( $user->ID );
        if ( is_wp_error( $result ) ) {
            \WP_CLI::warning(
                sprintf(
                    'Failed to migrate user %d: %s',
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

1. **Q: What happens to empty or invalid measurements?**
   - A: Optional measurements (chest, waist, etc.) can be null. Required fields (height, weight) must be valid numbers or a `WP_Error` is returned.

2. **Q: How are unit conversions handled?**
   - A: All data is stored in metric units in the database. Conversions happen in the frontend based on user preferences.

3. **Q: What about historical data tracking?**
   - A: Each measurement update creates a new record in `wp_physical_measurements` with a timestamp, allowing for historical tracking.

4. **Q: How do we handle migration failures?**
   - A: Failed migrations are logged and can be retried via WP-CLI. The original data remains intact until successful migration.

## Migration Checklist
- [ ] Review and document existing physical data structure
- [ ] Implement service layer methods
- [ ] Create React component with unit conversion
- [ ] Add validation rules
- [ ] Write tests
- [ ] Update documentation
- [ ] Verify data preservation
- [ ] Test edge cases
- [ ] Deploy incrementally 