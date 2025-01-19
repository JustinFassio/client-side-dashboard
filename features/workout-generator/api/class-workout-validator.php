<?php
/**
 * Workout Validator
 */

class Workout_Validator {
    /**
     * Validate a workout plan
     */
    public function validate($workout, $rules) {
        $errors = [];

        // Validate basic structure
        if (!is_array($workout) || !isset($workout['exercises']) || !is_array($workout['exercises'])) {
            return [
                'isValid' => false,
                'errors' => [[
                    'code' => 'INVALID_STRUCTURE',
                    'message' => 'Workout plan must contain an array of exercises'
                ]]
            ];
        }

        // Check number of exercises
        if (count($workout['exercises']) > $rules['maxExercises']) {
            $errors[] = [
                'code' => 'TOO_MANY_EXERCISES',
                'message' => "Workout contains more than {$rules['maxExercises']} exercises",
                'details' => [
                    'count' => count($workout['exercises']),
                    'max' => $rules['maxExercises']
                ]
            ];
        }

        // Validate rest periods
        $invalid_rest_periods = array_filter($workout['exercises'], function($exercise) use ($rules) {
            return isset($exercise['restPeriod']) && $exercise['restPeriod'] < $rules['minRestPeriod'];
        });

        if (!empty($invalid_rest_periods)) {
            $errors[] = [
                'code' => 'INSUFFICIENT_REST',
                'message' => "Some exercises have rest periods shorter than {$rules['minRestPeriod']} seconds",
                'details' => [
                    'exercises' => array_map(function($e) { return $e['id']; }, $invalid_rest_periods)
                ]
            ];
        }

        // Check for warmup if required
        if ($rules['requiredWarmup']) {
            $has_warmup = false;
            foreach ($workout['exercises'] as $exercise) {
                if (
                    (isset($exercise['type']) && $exercise['type'] === 'warmup') ||
                    (isset($exercise['tags']) && in_array('warmup', $exercise['tags']))
                ) {
                    $has_warmup = true;
                    break;
                }
            }

            if (!$has_warmup) {
                $errors[] = [
                    'code' => 'MISSING_WARMUP',
                    'message' => 'Workout plan must include a warmup exercise'
                ];
            }
        }

        // Validate individual exercises
        foreach ($workout['exercises'] as $exercise) {
            $exercise_errors = $this->validate_exercise($exercise);
            if (!empty($exercise_errors)) {
                $errors = array_merge($errors, $exercise_errors);
            }
        }

        // Validate exercise sequence
        $sequence_errors = $this->validate_exercise_sequence($workout['exercises']);
        if (!empty($sequence_errors)) {
            $errors = array_merge($errors, $sequence_errors);
        }

        return [
            'isValid' => empty($errors),
            'errors' => !empty($errors) ? $errors : null
        ];
    }

    /**
     * Validate a single exercise
     */
    private function validate_exercise($exercise) {
        $errors = [];

        // Check required fields
        if (!isset($exercise['id'])) {
            $errors[] = [
                'code' => 'MISSING_ID',
                'message' => 'Exercise must have an ID',
                'details' => ['exercise' => $exercise]
            ];
        }

        if (!isset($exercise['name'])) {
            $errors[] = [
                'code' => 'MISSING_NAME',
                'message' => 'Exercise must have a name',
                'details' => ['exerciseId' => $exercise['id'] ?? null]
            ];
        }

        // Validate sets and reps
        if (!isset($exercise['sets']) || !is_numeric($exercise['sets']) || $exercise['sets'] <= 0) {
            $errors[] = [
                'code' => 'INVALID_SETS',
                'message' => 'Exercise must have a valid number of sets',
                'details' => [
                    'exerciseId' => $exercise['id'] ?? null,
                    'sets' => $exercise['sets'] ?? null
                ]
            ];
        }

        if (!isset($exercise['reps']) || !is_numeric($exercise['reps']) || $exercise['reps'] <= 0) {
            $errors[] = [
                'code' => 'INVALID_REPS',
                'message' => 'Exercise must have a valid number of reps',
                'details' => [
                    'exerciseId' => $exercise['id'] ?? null,
                    'reps' => $exercise['reps'] ?? null
                ]
            ];
        }

        // Validate intensity if present
        if (isset($exercise['intensity'])) {
            if (!is_numeric($exercise['intensity']) || 
                $exercise['intensity'] < 0 || 
                $exercise['intensity'] > 100) {
                $errors[] = [
                    'code' => 'INVALID_INTENSITY',
                    'message' => 'Exercise intensity must be between 0 and 100',
                    'details' => [
                        'exerciseId' => $exercise['id'] ?? null,
                        'intensity' => $exercise['intensity']
                    ]
                ];
            }
        }

        return $errors;
    }

    /**
     * Validate exercise sequence
     */
    private function validate_exercise_sequence($exercises) {
        $errors = [];

        // Find indices for sequence validation
        $last_warmup_index = -1;
        $first_non_warmup_index = -1;
        $first_cooldown_index = -1;
        $last_non_cooldown_index = -1;

        foreach ($exercises as $index => $exercise) {
            $is_warmup = (isset($exercise['type']) && $exercise['type'] === 'warmup') ||
                        (isset($exercise['tags']) && in_array('warmup', $exercise['tags']));
            
            $is_cooldown = (isset($exercise['type']) && $exercise['type'] === 'cooldown') ||
                          (isset($exercise['tags']) && in_array('cooldown', $exercise['tags']));

            if ($is_warmup) {
                $last_warmup_index = $index;
            } elseif ($first_non_warmup_index === -1) {
                $first_non_warmup_index = $index;
            }

            if ($is_cooldown && $first_cooldown_index === -1) {
                $first_cooldown_index = $index;
            } elseif (!$is_cooldown) {
                $last_non_cooldown_index = $index;
            }
        }

        // Check warmup sequence
        if ($last_warmup_index > $first_non_warmup_index && $first_non_warmup_index !== -1) {
            $errors[] = [
                'code' => 'INVALID_WARMUP_SEQUENCE',
                'message' => 'Warmup exercises must come before main exercises'
            ];
        }

        // Check cooldown sequence
        if ($first_cooldown_index < $last_non_cooldown_index && $first_cooldown_index !== -1) {
            $errors[] = [
                'code' => 'INVALID_COOLDOWN_SEQUENCE',
                'message' => 'Cooldown exercises must come after main exercises'
            ];
        }

        return $errors;
    }
} 