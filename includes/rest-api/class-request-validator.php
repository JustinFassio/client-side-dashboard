<?php
namespace AthleteDashboard\RestApi;

if (!defined('ABSPATH')) {
    exit;
}

class Request_Validator {
    /**
     * Validate and sanitize request data
     *
     * @param array  $data    The request data to validate
     * @param array  $rules   Validation rules
     * @param string $context The validation context (create/update)
     * @return array|WP_Error Sanitized data or WP_Error if validation fails
     */
    public static function validate($data, $rules, $context = 'create') {
        return self::validate_data($data, $rules, $context);
    }

    /**
     * Internal validation method that supports recursion for nested structures
     *
     * @param array  $data    The data to validate
     * @param array  $rules   The validation rules
     * @param string $context The validation context
     * @param string $prefix  The current field prefix for nested errors
     * @return array|WP_Error Validated data or error
     */
    private static function validate_data($data, $rules, $context = 'create', $prefix = '') {
        $sanitized = array();
        $errors = array();
        
        foreach ($rules as $field => $rule) {
            $field_path = $prefix ? "{$prefix}.{$field}" : $field;
            
            // Handle nested objects
            if (isset($rule['properties'])) {
                if (!isset($data[$field]) || !is_array($data[$field])) {
                    if (!empty($rule['required']) && $context === 'create') {
                        $errors[$field_path] = sprintf(
                            __('%s is required and must be an object.', 'athlete-dashboard'),
                            $rule['label']
                        );
                    }
                    continue;
                }
                
                $nested_result = self::validate_data(
                    $data[$field],
                    $rule['properties'],
                    $context,
                    $field_path
                );
                
                if (is_wp_error($nested_result)) {
                    $error_data = $nested_result->get_error_data();
                    $errors = array_merge($errors, $error_data['errors']);
                } else {
                    $sanitized[$field] = $nested_result;
                }
                continue;
            }
            
            // Handle arrays of objects
            if (isset($rule['items'])) {
                if (!isset($data[$field])) {
                    if (!empty($rule['required']) && $context === 'create') {
                        $errors[$field_path] = sprintf(
                            __('%s is required.', 'athlete-dashboard'),
                            $rule['label']
                        );
                    }
                    continue;
                }
                
                if (!is_array($data[$field])) {
                    $errors[$field_path] = sprintf(
                        __('%s must be an array.', 'athlete-dashboard'),
                        $rule['label']
                    );
                    continue;
                }
                
                $sanitized[$field] = array();
                foreach ($data[$field] as $index => $item) {
                    $item_result = self::validate_data(
                        $item,
                        $rule['items'],
                        $context,
                        "{$field_path}[{$index}]"
                    );
                    
                    if (is_wp_error($item_result)) {
                        $error_data = $item_result->get_error_data();
                        $errors = array_merge($errors, $error_data['errors']);
                    } else {
                        $sanitized[$field][] = $item_result;
                    }
                }
                continue;
            }
            
            // Skip if field is not required and not present
            if (!isset($data[$field])) {
                if (!empty($rule['required']) && $context === 'create') {
                    $errors[$field_path] = sprintf(
                        __('%s is required.', 'athlete-dashboard'),
                        $rule['label']
                    );
                }
                continue;
            }
            
            $value = $data[$field];
            
            // Type validation and sanitization
            $type_result = self::validate_type($value, $rule, $field_path);
            if (is_wp_error($type_result)) {
                $errors[$field_path] = $type_result->get_error_message();
                continue;
            }
            $sanitized[$field] = $type_result;
            
            // Additional validations
            $validation_result = self::validate_field($value, $rule, $field_path);
            if (is_wp_error($validation_result)) {
                $errors[$field_path] = $validation_result->get_error_message();
            }
        }
        
        if (!empty($errors)) {
            return new \WP_Error(
                'validation_failed',
                __('Validation failed.', 'athlete-dashboard'),
                array(
                    'status' => 400,
                    'errors' => $errors
                )
            );
        }
        
        return $sanitized;
    }
    
    /**
     * Validate and sanitize a value based on its type
     *
     * @param mixed  $value The value to validate
     * @param array  $rule  The validation rule
     * @param string $field The field path for error messages
     * @return mixed|WP_Error Sanitized value or error
     */
    private static function validate_type($value, $rule, $field) {
        switch ($rule['type']) {
            case 'string':
                if (!is_string($value)) {
                    return new \WP_Error(
                        'invalid_type',
                        sprintf(__('%s must be a string.', 'athlete-dashboard'), $rule['label'])
                    );
                }
                return sanitize_text_field($value);
                
            case 'email':
                if (!is_email($value)) {
                    return new \WP_Error(
                        'invalid_email',
                        sprintf(__('%s must be a valid email address.', 'athlete-dashboard'), $rule['label'])
                    );
                }
                return sanitize_email($value);
                
            case 'integer':
                if (!is_numeric($value)) {
                    return new \WP_Error(
                        'invalid_integer',
                        sprintf(__('%s must be a number.', 'athlete-dashboard'), $rule['label'])
                    );
                }
                return absint($value);
                
            case 'float':
                if (!is_numeric($value)) {
                    return new \WP_Error(
                        'invalid_float',
                        sprintf(__('%s must be a number.', 'athlete-dashboard'), $rule['label'])
                    );
                }
                return (float) $value;
                
            case 'boolean':
                return (bool) $value;
                
            case 'array':
                if (!is_array($value)) {
                    return new \WP_Error(
                        'invalid_array',
                        sprintf(__('%s must be an array.', 'athlete-dashboard'), $rule['label'])
                    );
                }
                return array_map('sanitize_text_field', $value);
                
            default:
                return new \WP_Error(
                    'invalid_type',
                    sprintf(__('Unknown type %s for field %s.', 'athlete-dashboard'), $rule['type'], $field)
                );
        }
    }
    
    /**
     * Validate a field against additional rules
     *
     * @param mixed  $value The value to validate
     * @param array  $rule  The validation rule
     * @param string $field The field path for error messages
     * @return true|WP_Error True if valid, WP_Error if invalid
     */
    private static function validate_field($value, $rule, $field) {
        // Length validation
        if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
            return new \WP_Error(
                'min_length',
                sprintf(
                    __('%s must be at least %d characters.', 'athlete-dashboard'),
                    $rule['label'],
                    $rule['min_length']
                )
            );
        }
        
        if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
            return new \WP_Error(
                'max_length',
                sprintf(
                    __('%s must not exceed %d characters.', 'athlete-dashboard'),
                    $rule['label'],
                    $rule['max_length']
                )
            );
        }
        
        // Range validation for numbers
        if (isset($rule['min']) && $value < $rule['min']) {
            return new \WP_Error(
                'min_value',
                sprintf(
                    __('%s must be at least %s.', 'athlete-dashboard'),
                    $rule['label'],
                    $rule['min']
                )
            );
        }
        
        if (isset($rule['max']) && $value > $rule['max']) {
            return new \WP_Error(
                'max_value',
                sprintf(
                    __('%s must not exceed %s.', 'athlete-dashboard'),
                    $rule['label'],
                    $rule['max']
                )
            );
        }
        
        // Pattern validation
        if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
            return new \WP_Error(
                'pattern_mismatch',
                sprintf(
                    __('%s does not match the required pattern.', 'athlete-dashboard'),
                    $rule['label']
                )
            );
        }
        
        // Custom validation
        if (isset($rule['validate_callback']) && is_callable($rule['validate_callback'])) {
            $result = call_user_func($rule['validate_callback'], $value);
            if (is_wp_error($result)) {
                return $result;
            }
        }
        
        return true;
    }
    
    /**
     * Get common validation rules for profile data
     *
     * @return array Validation rules
     */
    public static function get_profile_rules() {
        return array(
            'firstName' => array(
                'type' => 'string',
                'required' => true,
                'label' => __('First Name', 'athlete-dashboard'),
                'max_length' => 50,
                'pattern' => '/^[a-zA-Z\s\'-]+$/'
            ),
            'lastName' => array(
                'type' => 'string',
                'required' => true,
                'label' => __('Last Name', 'athlete-dashboard'),
                'max_length' => 50,
                'pattern' => '/^[a-zA-Z\s\'-]+$/'
            ),
            'email' => array(
                'type' => 'email',
                'required' => true,
                'label' => __('Email', 'athlete-dashboard')
            ),
            'age' => array(
                'type' => 'integer',
                'required' => true,
                'label' => __('Age', 'athlete-dashboard'),
                'min' => 13,
                'max' => 120
            ),
            'height' => array(
                'type' => 'float',
                'required' => true,
                'label' => __('Height', 'athlete-dashboard'),
                'min' => 0,
                'max' => 300
            ),
            'weight' => array(
                'type' => 'float',
                'required' => true,
                'label' => __('Weight', 'athlete-dashboard'),
                'min' => 0,
                'max' => 500
            ),
            'injuries' => array(
                'type' => 'array',
                'label' => __('Injuries', 'athlete-dashboard'),
                'items' => array(
                    'type' => 'object',
                    'properties' => array(
                        'name' => array(
                            'type' => 'string',
                            'required' => true,
                            'label' => __('Injury Name', 'athlete-dashboard'),
                            'max_length' => 100
                        ),
                        'description' => array(
                            'type' => 'string',
                            'required' => true,
                            'label' => __('Injury Description', 'athlete-dashboard'),
                            'max_length' => 500
                        ),
                        'date' => array(
                            'type' => 'string',
                            'required' => true,
                            'label' => __('Injury Date', 'athlete-dashboard'),
                            'pattern' => '/^\d{4}-\d{2}-\d{2}$/'
                        ),
                        'status' => array(
                            'type' => 'string',
                            'required' => true,
                            'label' => __('Injury Status', 'athlete-dashboard'),
                            'validate_callback' => function($value) {
                                $valid_statuses = array('active', 'recovered', 'recovering');
                                if (!in_array($value, $valid_statuses)) {
                                    return new \WP_Error(
                                        'invalid_status',
                                        __('Invalid injury status.', 'athlete-dashboard')
                                    );
                                }
                                return true;
                            }
                        )
                    )
                )
            )
        );
    }
} 