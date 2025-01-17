<?php
/**
 * Physical Data Migration Test
 *
 * @package AthleteDashboard\Features\Profile\Tests\Unit\Database
 */

namespace AthleteDashboard\Features\Profile\Tests\Unit\Database;

use AthleteDashboard\Features\Profile\Database\Physical_Data_Migrator;
use AthleteDashboard\Features\Profile\Database\Physical_Measurements_Table;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Class Physical_Data_Migration_Test
 */
class Physical_Data_Migration_Test extends TestCase {

	/**
	 * @var Physical_Data_Migrator|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $migrator;

	/**
	 * @var Physical_Measurements_Table|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $table_manager;

	/**
	 * @var stdClass
	 */
	private $wpdb;

	/**
	 * Set up test environment
	 */
	protected function setUp(): void {
		parent::setUp();

		// Mock WordPress $wpdb global
		$this->wpdb         = new stdClass();
		$this->wpdb->prefix = 'wp_';
		global $wpdb;
		$wpdb = $this->wpdb;

		// Mock the table manager
		$this->table_manager = $this->getMockBuilder( Physical_Measurements_Table::class )
			->onlyMethods( array( 'get_table_name', 'table_exists' ) )
			->getMock();
		$this->table_manager->method( 'get_table_name' )
			->willReturn( 'wp_physical_measurements' );
		$this->table_manager->method( 'table_exists' )
			->willReturn( true );

		// Create a real migrator instance for testing
		$this->migrator = new Physical_Data_Migrator();
	}

	/**
	 * Clean up test environment
	 */
	protected function tearDown(): void {
		global $wpdb;
		$wpdb = null;
		parent::tearDown();
	}

	/**
	 * Test table existence check
	 */
	public function test_table_exists() {
		$this->assertTrue( $this->table_manager->table_exists() );
	}

	/**
	 * Test migration log
	 */
	public function test_migration_log() {
		// Create a mock just for this test
		$migrator = $this->getMockBuilder( Physical_Data_Migrator::class )
			->onlyMethods( array( 'get_migration_log' ) )
			->getMock();

		$log_entry = array(
			'user_id'   => 1,
			'message'   => 'Test message',
			'timestamp' => '2024-03-20 12:00:00',
		);

		$migrator->method( 'get_migration_log' )
			->willReturn( array( $log_entry ) );

		$log = $migrator->get_migration_log();

		$this->assertIsArray( $log );
		$this->assertCount( 1, $log );
		$this->assertEquals( $log_entry, $log[0] );
	}

	/**
	 * Test dry run mode
	 */
	public function test_dry_run_mode() {
		$this->migrator->set_dry_run( true );

		// Use reflection to check private property
		$reflection = new \ReflectionClass( Physical_Data_Migrator::class );
		$property   = $reflection->getProperty( 'is_dry_run' );
		$property->setAccessible( true );

		$this->assertTrue( $property->getValue( $this->migrator ) );
	}

	/**
	 * Test measurement sanitization
	 */
	public function test_measurement_sanitization() {
		$reflection = new \ReflectionClass( Physical_Data_Migrator::class );
		$method     = $reflection->getMethod( 'sanitize_measurement' );
		$method->setAccessible( true );

		// Test valid number
		$this->assertEquals(
			180.5,
			$method->invoke( $this->migrator, '180.5' )
		);

		// Test empty value
		$this->assertNull(
			$method->invoke( $this->migrator, '' )
		);

		// Test invalid value
		$this->assertEquals(
			0.0,
			$method->invoke( $this->migrator, 'invalid' )
		);
	}

	/**
	 * Test unit system sanitization
	 */
	public function test_unit_system_sanitization() {
		$reflection = new \ReflectionClass( Physical_Data_Migrator::class );
		$method     = $reflection->getMethod( 'sanitize_unit_system' );
		$method->setAccessible( true );

		// Test valid values
		$this->assertEquals(
			'metric',
			$method->invoke( $this->migrator, 'metric' )
		);
		$this->assertEquals(
			'imperial',
			$method->invoke( $this->migrator, 'imperial' )
		);

		// Test invalid value
		$this->assertEquals(
			'metric',
			$method->invoke( $this->migrator, 'invalid' )
		);
	}
}
