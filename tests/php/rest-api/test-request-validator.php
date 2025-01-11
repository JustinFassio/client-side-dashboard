/**
 * Tests for the Request Validator functionality.
 *
 * @package Athlete_Dashboard
 */

use AthleteDashboard\RestApi\Request_Validator;

/**
 * Class Request_Validator_Test
 * Tests the request validation functionality for REST API endpoints.
 */
class Request_Validator_Test extends WP_UnitTestCase {
	/**
	 * Test nested object validation.
	 */
	public function test_nested_object_validation() {
		// Define schema.
		$schema = [
			'type' => 'object',
			'properties' => [
				'profile' => [
					'type' => 'object',
					'required' => ['name', 'age'],
					'properties' => [
						'name' => ['type' => 'string'],
						'age' => ['type' => 'integer'],
						'preferences' => [
							'type' => 'object',
							'properties' => [
								'notifications' => ['type' => 'boolean']
							]
						]
					]
				]
			]
		];

		// Test valid data.
		$valid_data = [
			'profile' => [
				'name' => 'John Doe',
				'age' => 25,
				'preferences' => [
					'notifications' => true
				]
			]
		];

		$result = Request_Validator::validate($valid_data, $schema);
		$this->assertTrue($result->is_valid());

		// Test invalid data.
		$invalid_data = [
			'profile' => [
				'name' => 'John Doe',
				'preferences' => [
					'notifications' => 'not_a_boolean'
				]
			]
		];

		$result = Request_Validator::validate($invalid_data, $schema);
		$this->assertFalse($result->is_valid());
	}

	/**
	 * Test array validation.
	 */
	public function test_array_validation() {
		// Define schema.
		$schema = [
			'type' => 'object',
			'properties' => [
				'tags' => [
					'type' => 'array',
					'items' => ['type' => 'string'],
					'minItems' => 1,
					'maxItems' => 5
				]
			]
		];

		// Test valid data.
		$valid_data = ['tags' => ['workout', 'cardio', 'strength']];
		$result = Request_Validator::validate($valid_data, $schema);
		$this->assertTrue($result->is_valid());

		// Test invalid data - empty array.
		$invalid_data = ['tags' => []];
		$result = Request_Validator::validate($invalid_data, $schema);
		$this->assertFalse($result->is_valid());

		// Test invalid data - wrong type.
		$invalid_data = ['tags' => [1, 2, 3]];
		$result = Request_Validator::validate($invalid_data, $schema);
		$this->assertFalse($result->is_valid());
	}

	/**
	 * Test validation context.
	 */
	public function test_validation_context() {
		// Define schema.
		$schema = [
			'type' => 'object',
			'properties' => [
				'id' => ['type' => 'integer'],
				'title' => ['type' => 'string']
			],
			'required' => ['title']
		];

		// Test create context.
		$create_data = ['title' => 'New Workout'];
		$result = Request_Validator::validate($create_data, $schema, 'create');
		$this->assertTrue($result->is_valid());

		// Test update context.
		$update_data = ['id' => 1, 'title' => 'Updated Workout'];
		$result = Request_Validator::validate($update_data, $schema, 'update');
		$this->assertTrue($result->is_valid());
	}

	/**
	 * Test pattern validation.
	 */
	public function test_pattern_validation() {
		// Define schema.
		$schema = [
			'type' => 'object',
			'properties' => [
				'email' => [
					'type' => 'string',
					'pattern' => '^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$'
				]
			]
		];

		// Test valid email.
		$valid_data = ['email' => 'test@example.com'];
		$result = Request_Validator::validate($valid_data, $schema);
		$this->assertTrue($result->is_valid());

		// Test invalid email.
		$invalid_data = ['email' => 'invalid-email'];
		$result = Request_Validator::validate($invalid_data, $schema);
		$this->assertFalse($result->is_valid());
	}

	/**
	 * Test type validation.
	 */
	public function test_type_validation() {
		// Define schema.
		$schema = [
			'type' => 'object',
			'properties' => [
				'age' => ['type' => 'integer'],
				'name' => ['type' => 'string'],
				'active' => ['type' => 'boolean']
			]
		];

		// Test valid types.
		$valid_data = [
			'age' => 25,
			'name' => 'John',
			'active' => true
		];
		$result = Request_Validator::validate($valid_data, $schema);
		$this->assertTrue($result->is_valid());

		// Test invalid types.
		$invalid_data = [
			'age' => '25',
			'name' => 123,
			'active' => 'true'
		];
		$result = Request_Validator::validate($invalid_data, $schema);
		$this->assertFalse($result->is_valid());
	}

	/**
	 * Test range validation.
	 */
	public function test_range_validation() {
		// Define schema.
		$schema = [
			'type' => 'object',
			'properties' => [
				'age' => [
					'type' => 'integer',
					'minimum' => 18,
					'maximum' => 100
				],
				'score' => [
					'type' => 'number',
					'minimum' => 0,
					'maximum' => 100,
					'multipleOf' => 0.5
				]
			]
		];

		// Test valid ranges.
		$valid_data = [
			'age' => 25,
			'score' => 85.5
		];
		$result = Request_Validator::validate($valid_data, $schema);
		$this->assertTrue($result->is_valid());

		// Test invalid ranges.
		$invalid_data = [
			'age' => 15,
			'score' => 101
		];
		$result = Request_Validator::validate($invalid_data, $schema);
		$this->assertFalse($result->is_valid());
	}

	/**
	 * Test custom validation.
	 */
	public function test_custom_validation() {
		// Define schema with custom validation.
		$schema = [
			'type' => 'object',
			'properties' => [
				'workout_date' => [
					'type' => 'string',
					'format' => 'date'
				]
			],
			'custom' => function($data) {
				// Custom validation logic.
				$date = strtotime($data['workout_date']);
				return $date <= time();
			}
		];

		// Test valid date.
		$valid_data = [
			'workout_date' => date('Y-m-d', strtotime('-1 day'))
		];
		$result = Request_Validator::validate($valid_data, $schema);
		$this->assertTrue($result->is_valid());

		// Test invalid date.
		$invalid_data = [
			'workout_date' => date('Y-m-d', strtotime('+1 day'))
		];
		$result = Request_Validator::validate($invalid_data, $schema);
		$this->assertFalse($result->is_valid());
	}
}
