<?php

namespace AthleteDashboard\Features\WorkoutGenerator;

class Bootstrap {
    private $settings = [];
    private $enabled_features = [];

    public function __construct(array $settings = []) {
        $this->settings = $settings;
        $this->enabled_features = \get_option('workout_generator_settings', [
            'tier_settings' => [
                'foundation' => [
                    'analytics' => false,
                    'nutrition_tracking' => false,
                    'habit_tracking' => false
                ],
                'performance' => [
                    'analytics' => true,
                    'nutrition_tracking' => false,
                    'habit_tracking' => false
                ],
                'transformation' => [
                    'analytics' => true,
                    'nutrition_tracking' => true,
                    'habit_tracking' => true
                ]
            ]
        ]);
    }

    public function get_tier_settings($tier) {
        $defaults = [
            'foundation' => [
                'requests' => 60,
                'window' => 3600
            ],
            'performance' => [
                'requests' => 120,
                'window' => 3600
            ],
            'transformation' => [
                'requests' => 180,
                'window' => 3600
            ]
        ];

        return $this->settings[$tier] ?? $defaults[$tier] ?? $defaults['foundation'];
    }

    public function is_feature_enabled($feature, $tier = null) {
        if ($tier === null) {
            $tier = \apply_filters('athlete_dashboard_get_user_tier', 'foundation');
        }

        if (!isset($this->enabled_features['tier_settings'][$tier])) {
            return false;
        }

        return $this->enabled_features['tier_settings'][$tier][$feature] ?? false;
    }

    public function sanitize_settings($input) {
        if (!isset($input['tier_settings']) || !is_array($input['tier_settings'])) {
            return $this->get_default_settings();
        }

        $defaults = $this->get_default_settings();
        $sanitized = $defaults;

        foreach (['foundation', 'performance', 'transformation'] as $tier) {
            if (isset($input['tier_settings'][$tier])) {
                // For foundation tier, always enforce default values
                if ($tier === 'foundation') {
                    $sanitized['tier_settings'][$tier] = $defaults['tier_settings'][$tier];
                    continue;
                }

                // For other tiers, validate and sanitize each feature
                foreach (['analytics', 'nutrition_tracking', 'habit_tracking'] as $feature) {
                    if (isset($input['tier_settings'][$tier][$feature])) {
                        $sanitized['tier_settings'][$tier][$feature] = 
                            (bool) $input['tier_settings'][$tier][$feature];
                    }
                }

                // Enforce tier-specific defaults
                if ($tier === 'performance') {
                    $sanitized['tier_settings'][$tier]['nutrition_tracking'] = false;
                    $sanitized['tier_settings'][$tier]['habit_tracking'] = false;
                }
            }
        }

        return $sanitized;
    }

    private function get_default_settings() {
        return [
            'tier_settings' => [
                'foundation' => [
                    'analytics' => false,
                    'nutrition_tracking' => false,
                    'habit_tracking' => false
                ],
                'performance' => [
                    'analytics' => true,
                    'nutrition_tracking' => false,
                    'habit_tracking' => false
                ],
                'transformation' => [
                    'analytics' => true,
                    'nutrition_tracking' => true,
                    'habit_tracking' => true
                ]
            ]
        ];
    }

    public function sanitize_tier_settings($settings) {
        if (!is_array($settings)) {
            return false;
        }

        if (!isset($settings['requests']) || !is_numeric($settings['requests']) || $settings['requests'] < 1) {
            return false;
        }

        if (!isset($settings['window']) || !is_numeric($settings['window']) || $settings['window'] < 60) {
            return false;
        }

        return [
            'requests' => (int) $settings['requests'],
            'window' => (int) $settings['window']
        ];
    }
} 