<?php
/**
 * Migration Commands.
 *
 * @package AthleteDashboard\Features\Profile\API\CLI
 */

namespace AthleteDashboard\Features\Profile\API\CLI;

use AthleteDashboard\Features\Profile\API\Profile_Routes;
use AthleteDashboard\Features\Profile\API\Migration\Endpoint_Verifier;
use WP_CLI;

/**
 * WP-CLI commands for managing endpoint migrations.
 */
class Migration_Commands {
	/**
	 * Profile routes instance.
	 *
	 * @var Profile_Routes
	 */
	private Profile_Routes $routes;

	/**
	 * Endpoint verifier instance.
	 *
	 * @var Endpoint_Verifier
	 */
	private Endpoint_Verifier $verifier;

	/**
	 * Constructor.
	 *
	 * @param Profile_Routes $routes Profile routes instance.
	 */
	public function __construct( Profile_Routes $routes ) {
		$this->routes   = $routes;
		$this->verifier = new Endpoint_Verifier( $routes );
	}

	/**
	 * Compare responses between old and new User Get endpoints.
	 *
	 * ## OPTIONS
	 *
	 * [--user-id=<id>]
	 * : The user ID to test with. Defaults to current user.
	 *
	 * [--verbose]
	 * : Show detailed comparison output.
	 *
	 * ## EXAMPLES
	 *
	 *     wp athlete-profile compare-responses
	 *     wp athlete-profile compare-responses --user-id=123
	 *     wp athlete-profile compare-responses --verbose
	 *
	 * @param array $args Command arguments.
	 * @param array $assoc_args Command options.
	 */
	public function compare_responses( $args, $assoc_args ): void {
		$user_id = isset( $assoc_args['user-id'] ) ? (int) $assoc_args['user-id'] : get_current_user_id();
		$verbose = isset( $assoc_args['verbose'] );

		WP_CLI::log( "Comparing responses for user ID: $user_id" );

		$result = $this->verifier->compare_responses( $user_id );

		if ( $result['match'] ) {
			WP_CLI::success( 'Responses match!' );
		} else {
			WP_CLI::warning( 'Responses differ.' );

			if ( $verbose ) {
				WP_CLI::log( "\nDifferences found:" );
				WP_CLI::log( print_r( $result['differences'], true ) );

				WP_CLI::log( "\nOld response:" );
				WP_CLI::log( print_r( $result['old'], true ) );

				WP_CLI::log( "\nNew response:" );
				WP_CLI::log( print_r( $result['new'], true ) );
			} else {
				WP_CLI::log( 'Use --verbose to see detailed differences.' );
			}
		}
	}

	/**
	 * Test the User Update endpoint with various scenarios.
	 *
	 * ## OPTIONS
	 *
	 * [--user-id=<id>]
	 * : The user ID to test with. Defaults to current user.
	 *
	 * [--verbose]
	 * : Show detailed test output.
	 *
	 * ## EXAMPLES
	 *
	 *     wp athlete-profile test-user-update
	 *     wp athlete-profile test-user-update --user-id=123
	 *     wp athlete-profile test-user-update --verbose
	 *
	 * @param array $args Command arguments.
	 * @param array $assoc_args Command options.
	 */
	public function test_user_update( $args, $assoc_args ): void {
		$user_id = isset( $assoc_args['user-id'] ) ? (int) $assoc_args['user-id'] : get_current_user_id();
		$verbose = isset( $assoc_args['verbose'] );

		WP_CLI::log( "Testing User Update endpoint for user ID: $user_id" );

		// Test updating email
		$test_email = 'test_' . time() . '@example.com';
		$result     = $this->verifier->test_update(
			$user_id,
			array( 'email' => $test_email )
		);

		if ( $result['success'] ) {
			WP_CLI::success( 'Email update test passed!' );
		} else {
			WP_CLI::error( 'Email update test failed: ' . $result['message'] );
		}

		if ( $verbose ) {
			WP_CLI::log( "\nTest details:" );
			WP_CLI::log( print_r( $result, true ) );
		}

		// Test updating meta
		$result = $this->verifier->test_update(
			$user_id,
			array(
				'meta' => array(
					'rich_editing' => true,
					'admin_color'  => 'fresh',
				),
			)
		);

		if ( $result['success'] ) {
			WP_CLI::success( 'Meta update test passed!' );
		} else {
			WP_CLI::error( 'Meta update test failed: ' . $result['message'] );
		}

		if ( $verbose ) {
			WP_CLI::log( "\nTest details:" );
			WP_CLI::log( print_r( $result, true ) );
		}
	}
}
