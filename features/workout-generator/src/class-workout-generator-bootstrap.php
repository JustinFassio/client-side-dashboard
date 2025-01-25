<?php
/**
 * Bootstrap class for the Workout Generator feature
 */

namespace AthleteDashboard\Features\WorkoutGenerator;

use AthleteDashboard\Features\WorkoutGenerator\API\Workout_Endpoints;
use AthleteDashboard\Features\WorkoutGenerator\API\AI_Service;
use AthleteDashboard\Features\WorkoutGenerator\API\Rate_Limiter;
use AthleteDashboard\Features\WorkoutGenerator\API\Workout_Validator;

class Workout_Generator_Bootstrap {
	private $settings      = array();
	private $tier_features = array(
		'foundation'     => array(
			'analytics'          => false,
			'nutrition_tracking' => false,
			'habit_tracking'     => false,
		),
		'performance'    => array(
			'analytics'          => true,
			'nutrition_tracking' => false,
			'habit_tracking'     => false,
		),
		'transformation' => array(
			'analytics'          => true,
			'nutrition_tracking' => true,
			'habit_tracking'     => true,
		),
	);

	public function init() {
		error_log( 'Initializing workout generator feature...' );
		$this->load_dependencies();
		$this->setup_configuration();
		$this->register_endpoints();
		$this->register_assets();
		$this->register_settings();
		error_log( 'Workout generator feature initialized' );
	}

	public function load_dependencies() {
		error_log( 'Loading workout generator dependencies...' );
		require_once dirname( __DIR__ ) . '/config/ai-service-config.php';
		require_once dirname( __DIR__ ) . '/api/class-ai-service.php';
		require_once dirname( __DIR__ ) . '/api/class-rate-limiter.php';
		require_once dirname( __DIR__ ) . '/api/class-workout-validator.php';
		require_once dirname( __DIR__ ) . '/api/class-workout-endpoints.php';
		require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/features/profile/services/class-profile-service.php';
		error_log( 'Workout generator dependencies loaded' );
	}

	public function setup_configuration() {
		$this->settings = \get_option( 'workout_generator_settings', array() );
	}

	public function register_endpoints() {
		error_log( 'Registering workout generator endpoints...' );
		$endpoints = new Workout_Endpoints();
		$endpoints->register_routes();
		error_log( 'Workout generator endpoints registered' );
	}

	public function register_assets() {
		// Register scripts and styles
		\add_action(
			'wp_enqueue_scripts',
			function () {
				\wp_enqueue_script(
					'workout-generator',
					\plugins_url( 'assets/js/workout-generator.js', __DIR__ ),
					array( 'jquery' ),
					'1.0.0',
					true
				);
			}
		);
	}

	public function register_settings() {
		\add_action(
			'admin_init',
			function () {
				\register_setting(
					'workout_generator_options',
					'workout_generator_settings',
					array(
						'sanitize_callback' => array( $this, 'sanitize_settings' ),
					)
				);
			}
		);
	}

	public function sanitize_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $settings as $key => $value ) {
			switch ( $key ) {
				case 'endpoint':
					// Validate URL
					$sanitized[ $key ] = filter_var( $value, FILTER_VALIDATE_URL ) ? $value : '';
					break;
				case 'rate_limit':
					// Ensure positive integer
					$sanitized[ $key ] = max( 1, intval( $value ) );
					break;
				case 'rate_window':
					// Ensure minimum window of 60 seconds
					$sanitized[ $key ] = max( 60, intval( $value ) );
					break;
				case 'debug_mode':
					// Convert to boolean
					$sanitized[ $key ] = (bool) $value;
					break;
				default:
					$sanitized[ $key ] = \sanitize_text_field( $value );
			}
		}

		return $sanitized;
	}

	public function get_tier_settings( $tier ) {
		if ( ! isset( $this->tier_features[ $tier ] ) ) {
			return $this->tier_features['foundation'];
		}
		return $this->tier_features[ $tier ];
	}

	public function is_feature_enabled( $feature, $tier = 'foundation' ) {
		$tier_settings = $this->get_tier_settings( $tier );
		return isset( $tier_settings[ $feature ] ) ? $tier_settings[ $feature ] : false;
	}

	public function missing_api_key_notice() {
		if ( ! isset( $this->settings['api_key'] ) || empty( $this->settings['api_key'] ) ) {
			echo '<div class="notice notice-error"><p>Please configure your API key in the Workout Generator settings.</p></div>';
		}
	}

	public function render_settings_page() {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( 'Unauthorized access' );
		}

		include dirname( __DIR__ ) . '/templates/settings-page.php';
	}
}
