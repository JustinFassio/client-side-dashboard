<?php
/**
 * Test Data Loading Trait
 *
 * Provides functionality for loading test data from fixture files.
 *
 * @package AthleteDashboard\Features\Core\Testing\Fixtures\Traits
 */

namespace AthleteDashboard\Features\Core\Testing\Fixtures\Traits;

/**
 * Trait HasTestData
 */
trait HasTestData {
	/**
	 * Load test data from a JSON fixture file.
	 *
	 * @param string $fixture_name The name of the fixture file without extension.
	 * @return array The loaded fixture data.
	 * @throws \RuntimeException If the fixture file cannot be found or is invalid.
	 */
	protected function loadTestData( string $fixture_name ): array {
		$feature_path = $this->getFeaturePath();
		$fixture_file = sprintf( '%s/tests/fixtures/%s.json', $feature_path, $fixture_name );

		if ( ! file_exists( $fixture_file ) ) {
			throw new \RuntimeException( sprintf( 'Fixture file not found: %s', $fixture_file ) );
		}

		$data = json_decode( file_get_contents( $fixture_file ), true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			throw new \RuntimeException( sprintf( 'Invalid JSON in fixture file: %s', $fixture_file ) );
		}

		return $data;
	}

	/**
	 * Get the path to the current feature directory.
	 *
	 * @return string The feature directory path.
	 */
	abstract protected function getFeaturePath(): string;
} 