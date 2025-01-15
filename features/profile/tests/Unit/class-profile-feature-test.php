<?php
/**
 * Tests for the Profile Feature class.
 *
 * @package AthleteDashboard\Features\Profile\Tests\Unit
 */

namespace AthleteDashboard\Features\Profile\Tests\Unit;

use AthleteDashboard\Features\Core\Contracts\Feature_Contract;
use AthleteDashboard\Features\Profile\Profile_Feature;
use AthleteDashboard\Features\Profile\API\Profile_Routes;
use AthleteDashboard\Features\Profile\Services\Profile_Service;
use AthleteDashboard\Features\Profile\Services\User_Service;
use AthleteDashboard\Features\Profile\Events\Profile_Updated;
use WP_UnitTestCase;
use Mockery;

/**
 * Class Profile_Feature_Test
 */
class Profile_Feature_Test extends WP_UnitTestCase {
	/**
	 * Test that Profile_Feature implements Feature_Contract
	 */
	public function test_implements_feature_contract(): void {
		$routes = Mockery::mock( Profile_Routes::class );
		$feature = new Profile_Feature( $routes );
		
		$this->assertInstanceOf( Feature_Contract::class, $feature );
	}

	/**
	 * Test initialization of the feature
	 */
	public function test_init(): void {
		$routes = Mockery::mock( Profile_Routes::class );
		$routes->expects()->init()->once();
		
		$feature = new Profile_Feature( $routes );
		$feature->init();
	}

	/**
	 * Test public API exposure
	 */
	public function test_get_public_api(): void {
		$routes = Mockery::mock( Profile_Routes::class );
		$feature = new Profile_Feature( $routes );
		
		$api = $feature->get_public_api();
		
		$this->assertArrayHasKey( 'services', $api );
		$this->assertArrayHasKey( 'events', $api );
		$this->assertArrayHasKey( 'endpoints', $api );
		
		$this->assertContains( Profile_Service::class, $api['services'] );
		$this->assertContains( User_Service::class, $api['services'] );
		$this->assertContains( Profile_Updated::class, $api['events'] );
		$this->assertContains( Profile_Routes::class, $api['endpoints'] );
	}

	/**
	 * Test feature dependencies
	 */
	public function test_get_dependencies(): void {
		$routes = Mockery::mock( Profile_Routes::class );
		$feature = new Profile_Feature( $routes );
		
		$dependencies = $feature->get_dependencies();
		
		$this->assertIsArray( $dependencies );
		// Currently expecting empty array as there are no dependencies
		$this->assertEmpty( $dependencies );
	}

	/**
	 * Test event subscriptions
	 */
	public function test_get_event_subscriptions(): void {
		$routes = Mockery::mock( Profile_Routes::class );
		$feature = new Profile_Feature( $routes );
		
		$subscriptions = $feature->get_event_subscriptions();
		
		$this->assertIsArray( $subscriptions );
		// Currently expecting empty array as there are no subscriptions
		$this->assertEmpty( $subscriptions );
	}

	/**
	 * Clean up after each test
	 */
	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}
} 