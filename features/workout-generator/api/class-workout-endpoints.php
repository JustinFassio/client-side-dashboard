<?php
/**
 * Workout Generator REST API endpoints
 */

namespace AthleteDashboard\Features\WorkoutGenerator\API;

use Exception;
use WP_Error;
use WP_REST_Request;
use AthleteDashboard\Features\Profile\API\Profile_Service;

class Workout_Endpoints {
    /**
     * Register REST API routes
     */
    public function register_routes() {
        \register_rest_route('athlete-dashboard/v1', '/generate', [
            'methods'             => 'POST',
            'callback'           => [$this, 'generate_workout'],
            'permission_callback' => [$this, 'check_permission'],
            'args'               => [
                'preferences' => [
                    'required' => true,
                    'type'     => 'object',
                ],
            ],
        ]);

        \register_rest_route('athlete-dashboard/v1', '/modify', [
            'methods'             => 'POST',
            'callback'           => [$this, 'modify_workout'],
            'permission_callback' => [$this, 'check_permission'],
            'args'               => [
                'modifications' => [
                    'required' => true,
                    'type'     => 'object',
                ],
            ],
        ]);

        \register_rest_route('athlete-dashboard/v1', '/history', [
            'methods'             => 'GET',
            'callback'           => [$this, 'get_workout_history'],
            'permission_callback' => [$this, 'check_permission'],
            'args'               => [
                'filters' => [
                    'required' => false,
                    'type'     => 'object',
                ],
            ],
        ]);

        \register_rest_route('athlete-dashboard/v1', '/workout/alternative/(?P<exercise_id>[a-zA-Z0-9-]+)', [
            'methods'             => 'POST',
            'callback'           => [$this, 'get_exercise_alternative'],
            'permission_callback' => [$this, 'check_permission'],
            'args'               => [
                'constraints' => [
                    'required' => true,
                    'type'     => 'object',
                ],
            ],
        ]);
    }

    /**
     * Generate a new workout
     */
    public function generate_workout(WP_REST_Request $request) {
        try {
            $user_id = \get_current_user_id();
            $preferences = $request->get_json_params();

            // Validate preferences
            $this->validate_preferences($preferences);

            // Get user profile data
            $profile_service = new Profile_Service();
            $profile = $profile_service->get_profile($user_id);
            $training_prefs = $profile_service->get_training_preferences($user_id);
            $equipment = $profile_service->get_equipment_availability($user_id);

            // Generate AI prompt
            $prompt = [
                'profile' => $profile,
                'preferences' => $preferences,
                'trainingPreferences' => $training_prefs,
                'equipment' => $equipment,
            ];

            // Call AI service
            $ai_service = new AI_Service();
            $workout = $ai_service->generate_workout_plan($prompt);

            // Validate workout
            $validator = new Workout_Validator();
            $validation_result = $validator->validate($workout, [
                'maxExercises' => $preferences['maxExercises'] ?? 10,
                'minRestPeriod' => $preferences['minRestPeriod'] ?? 60,
                'requiredWarmup' => true,
            ]);

            if (!$validation_result['isValid']) {
                return new WP_Error(
                    'validation_failed',
                    'Generated workout failed validation',
                    ['status' => 400, 'errors' => $validation_result['errors']]
                );
            }

            return \rest_ensure_response($workout);

        } catch (Exception $e) {
            return new WP_Error(
                'generation_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Modify an existing workout
     */
    public function modify_workout(WP_REST_Request $request) {
        try {
            $workout_id = $request->get_param('id');
            $modifications = $request->get_json_params();

            // Get current workout
            $ai_service = new AI_Service();
            $current_workout = $ai_service->get_workout_by_id($workout_id);

            if (!$current_workout) {
                return new WP_Error(
                    'not_found',
                    'Workout not found',
                    ['status' => 404]
                );
            }

            // Apply modifications
            $modified_workout = $ai_service->modify_workout_plan($current_workout, $modifications);

            // Validate modified workout
            $validator = new Workout_Validator();
            $validation_result = $validator->validate($modified_workout, [
                'maxExercises' => $current_workout['preferences']['maxExercises'],
                'minRestPeriod' => $current_workout['preferences']['minRestPeriod'],
                'requiredWarmup' => true,
            ]);

            if (!$validation_result['isValid']) {
                return new WP_Error(
                    'validation_failed',
                    'Modified workout failed validation',
                    ['status' => 400, 'errors' => $validation_result['errors']]
                );
            }

            return \rest_ensure_response($modified_workout);

        } catch (Exception $e) {
            return new WP_Error(
                'modification_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Get workout history
     */
    public function get_workout_history(WP_REST_Request $request) {
        try {
            $user_id = \get_current_user_id();
            $filters = $request->get_param('filters');

            $ai_service = new AI_Service();
            $history = $ai_service->get_workout_history($user_id, $filters);

            return \rest_ensure_response($history);

        } catch (Exception $e) {
            return new WP_Error(
                'history_fetch_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Get alternative exercise
     */
    public function get_exercise_alternative(WP_REST_Request $request) {
        try {
            $exercise_id = $request->get_param('exercise_id');
            $constraints = $request->get_json_params();

            $ai_service = new AI_Service();
            
            // Get original exercise
            $exercise = $ai_service->get_exercise_by_id($exercise_id);
            if (!$exercise) {
                return new WP_Error(
                    'not_found',
                    'Exercise not found',
                    ['status' => 404]
                );
            }

            // Get alternatives
            $alternatives = $ai_service->suggest_alternatives($exercise, $constraints);
            
            if (empty($alternatives)) {
                return new WP_Error(
                    'no_alternatives',
                    'No suitable alternatives found',
                    ['status' => 404]
                );
            }

            return \rest_ensure_response($alternatives[0]);

        } catch (Exception $e) {
            return new WP_Error(
                'alternative_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Check if user has permission to access endpoints
     */
    public function check_permission() {
        return \is_user_logged_in();
    }

    /**
     * Validate workout preferences
     */
    private function validate_preferences($preferences) {
        if (!is_array($preferences)) {
            throw new Exception('Invalid preferences format');
        }

        // Validate maxExercises
        if (isset($preferences['maxExercises'])) {
            $max = intval($preferences['maxExercises']);
            if ($max <= 0 || $max > 50) {
                throw new Exception('maxExercises must be between 1 and 50');
            }
        }

        // Validate minRestPeriod
        if (isset($preferences['minRestPeriod'])) {
            $rest = intval($preferences['minRestPeriod']);
            if ($rest < 0 || $rest > 600) {
                throw new Exception('minRestPeriod must be between 0 and 600 seconds');
            }
        }

        // Validate intensity
        if (isset($preferences['intensity'])) {
            $intensity = intval($preferences['intensity']);
            if ($intensity < 1 || $intensity > 10) {
                throw new Exception('intensity must be between 1 and 10');
            }
        }

        // Validate equipment
        if (isset($preferences['equipment'])) {
            if (!is_array($preferences['equipment'])) {
                throw new Exception('equipment must be an array');
            }
            foreach ($preferences['equipment'] as $item) {
                if (!is_string($item) || empty($item)) {
                    throw new Exception('equipment items must be non-empty strings');
                }
            }
        }
    }
} 