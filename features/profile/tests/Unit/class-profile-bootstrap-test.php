<?php
/**
 * Profile Bootstrap Test.
 *
 * @package AthleteDashboard\Features\Profile\Tests\Unit
 */

namespace AthleteDashboard\Features\Profile\Tests\Unit;

use AthleteDashboard\Core\Container;
use AthleteDashboard\Core\Events;
use AthleteDashboard\Features\Profile\Profile_Bootstrap;
use AthleteDashboard\Features\Profile\Repository\Profile_Repository;
use AthleteDashboard\Features\Profile\Services\Profile_Service;
use AthleteDashboard\Features\Profile\Validation\Profile_Validator;
use AthleteDashboard\Features\User\Events\User_Updated;
use PHPUnit\Framework\TestCase;
use Mockery;

/**
 * Test case for the Profile Bootstrap class.
 */
class Profile_Bootstrap_Test extends TestCase {
	/**
	 * The container instance.
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * The bootstrap instance.
	 *
	 * @var Profile_Bootstrap
	 */
	private $bootstrap;

	/**
	 * Set up the test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->container = new Container();
		$this->bootstrap = new Profile_Bootstrap();
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
		$this->assertTrue( $this->container->has( Profile_Repository::class ) );
		$this->assertTrue( $this->container->has( Profile_Validator::class ) );
		$this->assertTrue( $this->container->has( Profile_Service::class ) );

		// Verify service instances
		$service = $this->container->get( Profile_Service::class );
		$this->assertInstanceOf( Profile_Service::class, $service );
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
				User_Updated::class,
				Mockery::type( 'Closure' )
			);

		// Register events
		$this->bootstrap->register_events( $this->container );
	}

	/**
	 * Test event listener handles User_Updated event.
	 */
	public function test_user_updated_listener_handles_event(): void {
		// Mock the Profile Service
		$profile_service = Mockery::mock( Profile_Service::class );
		$profile_service->shouldReceive( 'get_profile' )
			->once()
			->with( 1 )
			->andReturn(
				array(
					'id'    => 1,
					'email' => 'test@example.com',
				)
			);

		$profile_service->shouldReceive( 'update_profile' )
			->once()
			->with( 1, Mockery::type( 'array' ) )
			->andReturn(
				array(
					'id'    => 1,
					'email' => 'new@example.com',
				)
			);

		// Bind the mock service
		$this->container->singleton( Profile_Service::class, fn() => $profile_service );

		// Create a User_Updated event
		$event = new User_Updated(
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
		$listener  = reset( $listeners[ User_Updated::class ] );
		$listener( $event );
	}

	/**
	 * Test service singleton behavior.
	 */
	public function test_services_are_singletons(): void {
		$this->bootstrap->register_services( $this->container );

		$service1 = $this->container->get( Profile_Service::class );
		$service2 = $this->container->get( Profile_Service::class );

		$this->assertSame( $service1, $service2 );
	}
}
