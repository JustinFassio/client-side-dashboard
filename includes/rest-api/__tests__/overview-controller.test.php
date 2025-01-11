/**
 * Tests for the Overview Controller functionality.
 *
 * @package Athlete_Dashboard
 */

use AthleteDashboard\RestApi\Overview_Controller;
use WP_UnitTestCase;

/**
 * Class Overview_Controller_Test
 * Tests the overview controller functionality for REST API endpoints.
 */
class Overview_Controller_Test extends WP_UnitTestCase {
	/**
	 * The overview controller instance.
	 *
	 * @var Overview_Controller
	 */
	private $controller;

	/**
	 * The test user ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * The test admin ID.
	 *
	 * @var int
	 */
	private $admin_id;

	/**
	 * Set up the test environment.
	 */
	public function setUp() {
		parent::setUp();
		
		// Create test users.
		$this->user_id = $this->factory->user->create(['role' => 'subscriber']);
		$this->admin_id = $this->factory->user->create(['role' => 'administrator']);
		
		// Initialize controller.
		$this->controller = new Overview_Controller();
	}

	/**
	 * Clean up after tests.
	 */
	public function tearDown() {
		// Clean up test data.
		wp_delete_user($this->user_id);
		wp_delete_user($this->admin_id);
		parent::tearDown();
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		// Test route registration.
		$routes = $this->controller->register_routes();
		$this->assertNotEmpty($routes);
	}

	/**
	 * Test overview data retrieval.
	 */
	public function test_get_overview_data() {
		// Set up test data.
		wp_set_current_user($this->user_id);
		
		// Make the request.
		$request = new WP_REST_Request('GET', '/athlete-dashboard/v1/overview');
		$response = $this->controller->get_overview_data($request);
		
		// Verify response.
		$this->assertInstanceOf(WP_REST_Response::class, $response);
		$this->assertEquals(200, $response->get_status());
		
		// Check data structure.
		$data = $response->get_data();
		$this->assertArrayHasKey('goals', $data);
		$this->assertArrayHasKey('activities', $data);
		$this->assertArrayHasKey('stats', $data);
	}

	/**
	 * Test goal update functionality.
	 */
	public function test_update_goal() {
		// Set up test data.
		wp_set_current_user($this->user_id);
		
		// Create test request.
		$request = new WP_REST_Request('POST', '/athlete-dashboard/v1/overview/goal');
		$request->set_param('goal_id', 1);
		$request->set_param('status', 'completed');
		
		// Make the request.
		$response = $this->controller->update_goal($request);
		
		// Verify response.
		$this->assertInstanceOf(WP_REST_Response::class, $response);
		$this->assertEquals(200, $response->get_status());
		
		// Check updated data.
		$data = $response->get_data();
		$this->assertTrue($data['success']);
	}

	/**
	 * Test activity dismissal.
	 */
	public function test_dismiss_activity() {
		// Set up test data.
		wp_set_current_user($this->user_id);
		
		// Create test request.
		$request = new WP_REST_Request('POST', '/athlete-dashboard/v1/overview/activity/dismiss');
		$request->set_param('activity_id', 1);
		
		// Make the request.
		$response = $this->controller->dismiss_activity($request);
		
		// Verify response.
		$this->assertInstanceOf(WP_REST_Response::class, $response);
		$this->assertEquals(200, $response->get_status());
		
		// Check updated data.
		$data = $response->get_data();
		$this->assertTrue($data['success']);
	}

	/**
	 * Test permission checks.
	 */
	public function test_permission_check() {
		// Test unauthorized access.
		wp_set_current_user(0);
		$this->assertFalse($this->controller->check_permission());
		
		// Test subscriber access.
		wp_set_current_user($this->user_id);
		$this->assertTrue($this->controller->check_permission());
		
		// Test admin access.
		wp_set_current_user($this->admin_id);
		$this->assertTrue($this->controller->check_permission());
	}

	/**
	 * Test rate limiting functionality.
	 */
	public function test_rate_limiting() {
		// Set up test data.
		wp_set_current_user($this->user_id);
		
		// Make multiple requests.
		for ($i = 0; $i < 10; $i++) {
			$request = new WP_REST_Request('GET', '/athlete-dashboard/v1/overview');
			$response = $this->controller->get_overview_data($request);
			
			// Verify rate limiting headers.
			$headers = $response->get_headers();
			$this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
		}
	}
}
