<?php

class Test_Profile_Performance extends AD_UnitTestCase {
	private $start_time;
	private $metrics = array();

	protected function measure_time( string $operation, callable $callback ) {
		$this->start_time = microtime( true );
		$result           = $callback();
		$duration         = ( microtime( true ) - $this->start_time ) * 1000; // Convert to milliseconds

		if ( ! isset( $this->metrics[ $operation ] ) ) {
			$this->metrics[ $operation ] = array();
		}
		$this->metrics[ $operation ][] = $duration;

		return $result;
	}

	protected function get_average_time( string $operation ): float {
		if ( ! isset( $this->metrics[ $operation ] ) ) {
			return 0;
		}
		return array_sum( $this->metrics[ $operation ] ) / count( $this->metrics[ $operation ] );
	}

	public function test_physical_data_retrieval_performance() {
		$user_id          = $this->factory->user->create();
		$physical_service = new Physical_Service( $user_id );

		// Test uncached retrieval
		$this->measure_time(
			'db_get_physical',
			function () use ( $physical_service ) {
				return $physical_service->get_physical_data();
			}
		);

		// Test cached retrieval
		$this->measure_time(
			'cache_get_physical',
			function () use ( $physical_service ) {
				return $physical_service->get_physical_data();
			}
		);

		$db_time    = $this->get_average_time( 'db_get_physical' );
		$cache_time = $this->get_average_time( 'cache_get_physical' );

		$this->assertLessThan( 100, $db_time, 'Database retrieval took too long' );
		$this->assertLessThan( 50, $cache_time, 'Cache retrieval took too long' );
		$this->assertLessThan( $db_time, $cache_time, 'Cache should be faster than database' );
	}

	public function test_physical_data_update_performance() {
		$user_id          = $this->factory->user->create();
		$physical_service = new Physical_Service( $user_id );

		$data = array(
			'heightCm' => 180,
			'weightKg' => 75,
			'units'    => array(
				'height' => 'cm',
				'weight' => 'kg',
			),
		);

		// Test update performance
		$this->measure_time(
			'update_physical',
			function () use ( $physical_service, $data ) {
				return $physical_service->update_physical_data( $data );
			}
		);

		$update_time = $this->get_average_time( 'update_physical' );
		$this->assertLessThan( 200, $update_time, 'Update operation took too long' );
	}

	public function test_concurrent_operations_performance() {
		$user_ids = array_map(
			function () {
				return $this->factory->user->create();
			},
			range( 1, 5 )
		);

		$services = array_map(
			function ( $user_id ) {
				return new Physical_Service( $user_id );
			},
			$user_ids
		);

		// Test concurrent reads
		$this->measure_time(
			'concurrent_reads',
			function () use ( $services ) {
				return array_map(
					function ( $service ) {
						return $service->get_physical_data();
					},
					$services
				);
			}
		);

		$concurrent_time = $this->get_average_time( 'concurrent_reads' );
		$this->assertLessThan( 300, $concurrent_time, 'Concurrent operations took too long' );
	}

	public function test_history_retrieval_performance() {
		$user_id          = $this->factory->user->create();
		$physical_service = new Physical_Service( $user_id );

		// Create some history entries
		for ( $i = 0; $i < 10; $i++ ) {
			$physical_service->save_to_history(
				array(
					'heightCm' => 180,
					'weightKg' => 75 + $i,
				)
			);
		}

		// Test history retrieval
		$this->measure_time(
			'get_history',
			function () use ( $physical_service ) {
				return $physical_service->get_physical_history();
			}
		);

		$history_time = $this->get_average_time( 'get_history' );
		$this->assertLessThan( 150, $history_time, 'History retrieval took too long' );
	}

	public function test_database_query_optimization() {
		global $wpdb;
		$user_id          = $this->factory->user->create();
		$physical_service = new Physical_Service( $user_id );

		// Monitor query count
		$initial_queries = $wpdb->num_queries;

		$this->measure_time(
			'optimized_operations',
			function () use ( $physical_service ) {
				$physical_service->get_physical_data();
				$physical_service->get_physical_history();
			}
		);

		$query_count = $wpdb->num_queries - $initial_queries;
		$this->assertLessThan( 5, $query_count, 'Too many database queries executed' );
	}
}
