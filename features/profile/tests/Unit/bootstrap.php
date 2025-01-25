<?php
/**
 * PHPUnit bootstrap file for Profile feature unit tests
 *
 * @package AthleteDashboard\Features\Profile\Tests
 */

// Load Composer's autoloader
require_once dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/vendor/autoload.php';

// Load validator classes
require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/profile/validation/class-base-validator.php';
require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/profile/validation/class-profile-validator.php';
require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/profile/validation/class-user-validator.php';

// Define WordPress constants needed for testing
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/' );
}

if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', true );
}

// Mock WordPress functions if they don't exist
if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value, ...$args ) {
		return $value;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( $email ) {
		return filter_var( $email, FILTER_SANITIZE_EMAIL );
	}
}

if ( ! function_exists( 'absint' ) ) {
	function absint( $number ) {
		return abs( intval( $number ) );
	}
}

if ( ! function_exists( 'is_email' ) ) {
	function is_email( $email ) {
		return filter_var( $email, FILTER_VALIDATE_EMAIL ) !== false;
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $errors     = array();
		private $error_data = array();

		public function __construct( $code = '', $message = '', $data = '' ) {
			if ( ! empty( $code ) ) {
				$this->errors[ $code ][] = $message;
				if ( ! empty( $data ) ) {
					$this->error_data[ $code ] = $data;
				}
			}
		}

		public function get_error_code() {
			$codes = array_keys( $this->errors );
			return empty( $codes ) ? '' : $codes[0];
		}

		public function get_error_message( $code = '' ) {
			if ( empty( $code ) ) {
				$code = $this->get_error_code();
			}
			return isset( $this->errors[ $code ][0] ) ? $this->errors[ $code ][0] : '';
		}

		public function get_error_data( $code = '' ) {
			if ( empty( $code ) ) {
				$code = $this->get_error_code();
			}
			return isset( $this->error_data[ $code ] ) ? $this->error_data[ $code ] : null;
		}
	}
}

// Mock Debug class if it doesn't exist
if ( ! class_exists( 'AthleteDashboard\Core\Config\Debug' ) ) {
	class Debug {
		public static function log( $message, $tag = '' ) {
			// No-op for testing
		}
	}
}

// Mock WordPress functions if they don't exist
if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability ) {
		return true;
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $string, $remove_breaks = false ) {
		$string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $string );
		$string = strip_tags( $string );
		if ( $remove_breaks ) {
			$string = preg_replace( '/[\r\n\t ]+/', ' ', $string );
		}
		return trim( $string );
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}
