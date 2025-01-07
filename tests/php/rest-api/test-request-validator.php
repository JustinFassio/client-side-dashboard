<?php
namespace AthleteDashboard\Tests\RestApi;

use AthleteDashboard\RestApi\Request_Validator;
use WP_UnitTestCase;

class Request_Validator_Test extends WP_UnitTestCase {
    public function test_nested_object_validation() {
        $rules = [
            'profile' => [
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                        'required' => true,
                        'label' => 'Name'
                    ],
                    'contact' => [
                        'type' => 'object',
                        'properties' => [
                            'email' => [
                                'type' => 'email',
                                'required' => true,
                                'label' => 'Email'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // Test valid nested data
        $valid_data = [
            'profile' => [
                'name' => 'John Doe',
                'contact' => [
                    'email' => 'john@example.com'
                ]
            ]
        ];
        $result = Request_Validator::validate($valid_data, $rules);
        $this->assertNotWPError($result);

        // Test invalid nested data
        $invalid_data = [
            'profile' => [
                'name' => 'John Doe',
                'contact' => [
                    'email' => 'invalid-email'
                ]
            ]
        ];
        $result = Request_Validator::validate($invalid_data, $rules);
        $this->assertWPError($result);
        $this->assertArrayHasKey('profile.contact.email', $result->get_error_data()['errors']);
    }

    public function test_array_validation() {
        $rules = Request_Validator::get_profile_rules();
        
        // Test valid injuries array
        $valid_data = [
            'injuries' => [
                [
                    'name' => 'Sprained Ankle',
                    'description' => 'Left ankle sprain',
                    'date' => '2024-01-15',
                    'status' => 'active'
                ]
            ]
        ];
        $result = Request_Validator::validate($valid_data, $rules, 'update');
        $this->assertNotWPError($result);

        // Test invalid injury data
        $invalid_data = [
            'injuries' => [
                [
                    'name' => 'Sprained Ankle',
                    'description' => 'Left ankle sprain',
                    'date' => 'invalid-date', // Invalid date format
                    'status' => 'unknown' // Invalid status
                ]
            ]
        ];
        $result = Request_Validator::validate($invalid_data, $rules, 'update');
        $this->assertWPError($result);
        $errors = $result->get_error_data()['errors'];
        $this->assertArrayHasKey('injuries[0].date', $errors);
        $this->assertArrayHasKey('injuries[0].status', $errors);
    }

    public function test_validation_context() {
        $rules = Request_Validator::get_profile_rules();
        
        // Test create context (all required fields)
        $create_data = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john@example.com',
            'age' => 25,
            'height' => 180,
            'weight' => 75
        ];
        $result = Request_Validator::validate($create_data, $rules, 'create');
        $this->assertNotWPError($result);

        // Test update context (partial update)
        $update_data = [
            'weight' => 76.5
        ];
        $result = Request_Validator::validate($update_data, $rules, 'update');
        $this->assertNotWPError($result);
    }

    public function test_pattern_validation() {
        $rules = Request_Validator::get_profile_rules();
        
        // Test valid name pattern
        $valid_data = [
            'firstName' => 'John-Paul',
            'lastName' => "O'Connor"
        ];
        $result = Request_Validator::validate($valid_data, $rules, 'update');
        $this->assertNotWPError($result);

        // Test invalid name pattern
        $invalid_data = [
            'firstName' => 'John123',
            'lastName' => 'Doe$'
        ];
        $result = Request_Validator::validate($invalid_data, $rules, 'update');
        $this->assertWPError($result);
        $errors = $result->get_error_data()['errors'];
        $this->assertArrayHasKey('firstName', $errors);
        $this->assertArrayHasKey('lastName', $errors);
    }

    public function test_type_validation() {
        $rules = Request_Validator::get_profile_rules();

        // Test numeric types
        $numeric_data = [
            'age' => '25', // String that should be converted to integer
            'height' => '180.5', // String that should be converted to float
            'weight' => 75.5
        ];
        $result = Request_Validator::validate($numeric_data, $rules, 'update');
        $this->assertNotWPError($result);
        $this->assertIsInt($result['age']);
        $this->assertIsFloat($result['height']);
        $this->assertIsFloat($result['weight']);

        // Test invalid numeric types
        $invalid_numeric = [
            'age' => 'twenty',
            'height' => 'tall',
            'weight' => 'heavy'
        ];
        $result = Request_Validator::validate($invalid_numeric, $rules, 'update');
        $this->assertWPError($result);
        $errors = $result->get_error_data()['errors'];
        $this->assertArrayHasKey('age', $errors);
        $this->assertArrayHasKey('height', $errors);
        $this->assertArrayHasKey('weight', $errors);
    }

    public function test_range_validation() {
        $rules = Request_Validator::get_profile_rules();

        // Test valid ranges
        $valid_ranges = [
            'age' => 25,
            'height' => 180,
            'weight' => 75
        ];
        $result = Request_Validator::validate($valid_ranges, $rules, 'update');
        $this->assertNotWPError($result);

        // Test out of range values
        $invalid_ranges = [
            'age' => 5, // Below minimum age
            'height' => 400, // Above maximum height
            'weight' => -1 // Below minimum weight
        ];
        $result = Request_Validator::validate($invalid_ranges, $rules, 'update');
        $this->assertWPError($result);
        $errors = $result->get_error_data()['errors'];
        $this->assertArrayHasKey('age', $errors);
        $this->assertArrayHasKey('height', $errors);
        $this->assertArrayHasKey('weight', $errors);
    }

    public function test_custom_validation() {
        $rules = Request_Validator::get_profile_rules();

        // Test valid injury status
        $valid_status = [
            'injuries' => [
                [
                    'name' => 'Sprained Ankle',
                    'description' => 'Left ankle sprain',
                    'date' => '2024-01-15',
                    'status' => 'recovering'
                ]
            ]
        ];
        $result = Request_Validator::validate($valid_status, $rules, 'update');
        $this->assertNotWPError($result);

        // Test custom validation callback
        $invalid_status = [
            'injuries' => [
                [
                    'name' => 'Sprained Ankle',
                    'description' => 'Left ankle sprain',
                    'date' => '2024-01-15',
                    'status' => 'pending' // Invalid status
                ]
            ]
        ];
        $result = Request_Validator::validate($invalid_status, $rules, 'update');
        $this->assertWPError($result);
        $errors = $result->get_error_data()['errors'];
        $this->assertArrayHasKey('injuries[0].status', $errors);
    }
} 