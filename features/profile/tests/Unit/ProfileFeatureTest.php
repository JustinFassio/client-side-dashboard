<?php
/**
 * Tests for the Profile Feature
 *
 * @package AthleteDashboard\Features\Profile\Tests\Unit
 */

namespace AthleteDashboard\Features\Profile\Tests\Unit;

use AthleteDashboard\Features\Core\Testing\TestCase\Unit\BaseUnitTest;
use AthleteDashboard\Features\Core\Testing\Fixtures\Traits\HasTestData;
use AthleteDashboard\Features\Core\Contracts\Feature_Contract;
use AthleteDashboard\Features\Profile\Profile_Feature;
use AthleteDashboard\Features\Profile\API\Profile_Routes;
use AthleteDashboard\Features\Profile\Services\Profile_Service;
use AthleteDashboard\Features\Profile\Services\User_Service;
use AthleteDashboard\Features\Profile\Events\Profile_Updated;

/**
 * Class ProfileFeatureTest
 */
class ProfileFeatureTest extends BaseUnitTest {
	use HasTestData;

	/**
	 * Test that Profile_Feature implements Feature_Contract
	 */
	public function test_implements_feature_contract(): void {
		$routes = $this->createMockery( Profile_Routes::class );
		$feature = new Profile_Feature( $routes );
		
		$this->assertImplementsContract( Feature_Contract::class, $feature );
	}

	/**
	 * Test that init method initializes routes
	 */
	public function test_init(): void {
		$routes = $this->createMockery( Profile_Routes::class );
		$routes->shouldReceive( 'init' )->once();
		
		$feature = new Profile_Feature( $routes );
		$feature->init();

		$this->assertTrue( true, 'Routes were initialized successfully' );
	}

	/**
	 * Test that get_public_api returns expected services and events
	 */
	public function test_get_public_api(): void {
		$routes = $this->createMockery( Profile_Routes::class );
		$feature = new Profile_Feature( $routes );
		
		$api = $feature->get_public_api();
		
		$this->assertIsArray( $api );
		$this->assertArrayHasKey( 'services', $api );
		$this->assertArrayHasKey( 'events', $api );
		$this->assertArrayHasKey( 'endpoints', $api );
		
		$this->assertContains( Profile_Service::class, $api['services'] );
		$this->assertContains( User_Service::class, $api['services'] );
		$this->assertContains( Profile_Updated::class, $api['events'] );
		$this->assertContains( Profile_Routes::class, $api['endpoints'] );
	}

	/**
	 * Test that get_dependencies returns expected dependencies
	 */
	public function test_get_dependencies(): void {
		$routes = $this->createMockery( Profile_Routes::class );
		$feature = new Profile_Feature( $routes );
		
		$dependencies = $feature->get_dependencies();
		
		$this->assertIsArray( $dependencies );
		$this->assertEmpty( $dependencies );
	}

	/**
	 * Test that get_event_subscriptions returns expected subscriptions
	 */
	public function test_get_event_subscriptions(): void {
		$routes = $this->createMockery( Profile_Routes::class );
		$feature = new Profile_Feature( $routes );
		
		$subscriptions = $feature->get_event_subscriptions();
		
		$this->assertIsArray( $subscriptions );
		$this->assertEmpty( $subscriptions );
	}

	/**
	 * Get the path to the current feature directory.
	 *
	 * @return string The feature directory path.
	 */
	protected function getFeaturePath(): string {
		return dirname( dirname( __DIR__ ) );
	}
} 