<?php
/**
 * Tests for the Profile Feature Contract implementation
 *
 * @package AthleteDashboard\Features\Profile\Tests\Unit\Contract
 */

namespace AthleteDashboard\Features\Profile\Tests\Unit\Contract;

use AthleteDashboard\Features\Core\Contracts\Feature_Contract;
use AthleteDashboard\Features\Profile\Profile_Feature;
use AthleteDashboard\Features\Profile\API\Profile_Routes;
use WP_UnitTestCase;
use Mockery;

/**
 * Class Profile_Feature_Contract_Test
 */
class Profile_Feature_Contract_Test extends WP_UnitTestCase {
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
		
		$this->assertIsArray( $api );
		$this->assertArrayHasKey( 'services', $api );
		$this->assertArrayHasKey( 'events', $api );
		$this->assertArrayHasKey( 'endpoints', $api );
	}

	/**
	 * Test feature dependencies
	 */
	public function test_get_dependencies(): void {
		$routes = Mockery::mock( Profile_Routes::class );
		$feature = new Profile_Feature( $routes );
		
		$dependencies = $feature->get_dependencies();
		
		$this->assertIsArray( $dependencies );
	}

	/**
	 * Test event subscriptions
	 */
	public function test_get_event_subscriptions(): void {
		$routes = Mockery::mock( Profile_Routes::class );
		$feature = new Profile_Feature( $routes );
		
		$subscriptions = $feature->get_event_subscriptions();
		
		$this->assertIsArray( $subscriptions );
	}

	/**
	 * Clean up after each test
	 */
	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}
} 