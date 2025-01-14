<?php
/**
 * User Bootstrap Test.
 *
 * @package AthleteDashboard\Features\User\Tests\Unit
 */

namespace AthleteDashboard\Features\User\Tests\Unit;

use AthleteDashboard\Core\Container;
use AthleteDashboard\Core\Events;
use AthleteDashboard\Features\User\User_Bootstrap;
use AthleteDashboard\Features\User\Repository\User_Repository;
use AthleteDashboard\Features\User\Services\User_Service;
use AthleteDashboard\Features\User\Validation\User_Validator;
use AthleteDashboard\Features\Profile\Events\Profile_Updated;
use PHPUnit\Framework\TestCase;
use Mockery;

/**
 * Test case for the User Bootstrap class.
 */
class User_Bootstrap_Test extends TestCase {
	/**
	 * The container instance.
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * The bootstrap instance.
	 *
	 * @var User_Bootstrap
	 */
	private $bootstrap;

	/**
	 * Set up the test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->container = new Container();
		$this->bootstrap = new User_Bootstrap();
	}

	/**
	 * Clean up the test environment.
	 */
	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Test service registration.
	 */
	public function test_register_services(): void {
		// Register services
		$this->bootstrap->register_services( $this->container );

		// Verify service bindings
		$this->assertTrue( $this->container->has( User_Repository::class ) );
		$this->assertTrue( $this->container->has( User_Validator::class ) );
		$this->assertTrue( $this->container->has( User_Service::class ) );

		// Verify service instances
		$service = $this->container->get( User_Service::class );
		$this->assertInstanceOf( User_Service::class, $service );
	}

	/**
	 * Test event listener registration.
	 */
	public function test_register_events(): void {
		// Mock the Events class
		$events_mock = Mockery::mock( 'alias:' . Events::class );
		$events_mock->shouldReceive( 'listen' )
			->once()
			->with(
				Profile_Updated::class,
				Mockery::type( 'Closure' )
			);

		// Register events
		$this->bootstrap->register_events( $this->container );
	}

	/**
	 * Test event listener handles Profile_Updated event.
	 */
	public function test_profile_updated_listener_handles_event(): void {
		// Mock the User Service
		$user_service = Mockery::mock( User_Service::class );
		$user_service->shouldReceive( 'update_user' )
			->once()
			->with( 1, Mockery::type( 'array' ) )
			->andReturn(
				array(
					'id'    => 1,
					'email' => 'new@example.com',
				)
			);

		// Bind the mock service
		$this->container->singleton( User_Service::class, fn() => $user_service );

		// Create a Profile_Updated event
		$event = new Profile_Updated(
			1,
			array(
				'email'        => 'new@example.com',
				'display_name' => 'New Name',
			)
		);

		// Get the event listener
		$reflection = new \ReflectionClass( Events::class );
		$listeners  = $reflection->getProperty( 'listeners' );
		$listeners->setAccessible( true );
		$listeners->setValue( null, array() );

		// Register events
		$this->bootstrap->register_events( $this->container );

		// Simulate event dispatch
		$listeners = $listeners->getValue();
		$listener  = reset( $listeners[ Profile_Updated::class ] );
		$listener( $event );
	}

	/**
	 * Test service singleton behavior.
	 */
	public function test_services_are_singletons(): void {
		$this->bootstrap->register_services( $this->container );

		$service1 = $this->container->get( User_Service::class );
		$service2 = $this->container->get( User_Service::class );

		$this->assertSame( $service1, $service2 );
	}
}
