<?php
namespace AthleteDashboard\Features\Profile;

/**
 * Profile feature configuration
 * Manages settings specific to the profile feature
 */
class Config {
    /**
     * Profile field configuration
     */
    private const FIELDS = [
        'personal' => [
            'name' => [
                'enabled' => true,
                'required' => true,
                'type' => 'text'
            ],
            'age' => [
                'enabled' => true,
                'required' => true,
                'type' => 'number',
                'min' => 13,
                'max' => 120
            ],
            'gender' => [
                'enabled' => true,
                'required' => false,
                'type' => 'select',
                'options' => ['male', 'female', 'other', 'prefer_not_to_say']
            ],
            'height' => [
                'enabled' => true,
                'required' => false,
                'type' => 'number'
            ],
            'weight' => [
                'enabled' => true,
                'required' => false,
                'type' => 'number'
            ]
        ],
        'medical' => [
            'has_injuries' => [
                'enabled' => true,
                'required' => true,
                'type' => 'boolean'
            ],
            'injuries' => [
                'enabled' => true,
                'required' => false,
                'type' => 'textarea',
                'depends_on' => ['has_injuries' => true]
            ],
            'has_medical_clearance' => [
                'enabled' => true,
                'required' => true,
                'type' => 'boolean'
            ],
            'medical_clearance_date' => [
                'enabled' => true,
                'required' => false,
                'type' => 'date',
                'depends_on' => ['has_medical_clearance' => true]
            ]
        ],
        'preferences' => [
            'bio' => [
                'enabled' => true,
                'required' => false,
                'type' => 'textarea'
            ],
            'fitness_goals' => [
                'enabled' => true,
                'required' => false,
                'type' => 'textarea'
            ],
            'preferred_workout_types' => [
                'enabled' => true,
                'required' => false,
                'type' => 'multiselect',
                'options' => [
                    'strength',
                    'cardio',
                    'hiit',
                    'flexibility',
                    'sports_specific'
                ]
            ]
        ]
    ];

    /**
     * Get all profile settings
     * @return array Profile configuration
     */
    public static function get_settings(): array {
        return [
            'fields' => self::FIELDS,
            'meta_prefix' => 'athlete_',
            'events' => [
                'profile_updated' => 'profile:updated',
                'profile_loaded' => 'profile:loaded',
                'profile_error' => 'profile:error'
            ]
        ];
    }

    /**
     * Get field configuration
     * @param string $section Section name
     * @param string $field Field name
     * @return array|null Field configuration or null if not found
     */
    public static function get_field_config(string $section, string $field): ?array {
        return self::FIELDS[$section][$field] ?? null;
    }

    /**
     * Check if a field is enabled
     * @param string $section Section name
     * @param string $field Field name
     * @return bool
     */
    public static function is_field_enabled(string $section, string $field): bool {
        $config = self::get_field_config($section, $field);
        return $config ? ($config['enabled'] ?? false) : false;
    }

    /**
     * Get meta key for a field
     * @param string $field Field name
     * @return string
     */
    public static function get_meta_key(string $field): string {
        $settings = self::get_settings();
        return $settings['meta_prefix'] . $field;
    }
} 