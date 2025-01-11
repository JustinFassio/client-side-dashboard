<?php
/**
 * PHPUnit bootstrap file
 */

// First, let's try to load the composer autoloader
$autoloader = dirname( __DIR__ ) . '/vendor/autoload.php';
if ( ! file_exists( $autoloader ) ) {
	die( 'Please run `composer install` before running tests.' );
}
require_once $autoloader;

// Load test configuration
require_once __DIR__ . '/config.php';

// Load test case base class first
require_once __DIR__ . '/php/TestCase.php';

// Initialize global variables
global $test_log_messages;
$test_log_messages = array();

// Initialize Logger first
echo "\nBootstrap - Initializing Logger";
\AthleteDashboard\Tests\Logger::getInstance();

// Define error_log function to use our global array
if ( ! function_exists( 'error_log' ) ) {
	/**
	 * Mock error_log function that captures messages for testing
	 */
	function error_log( $message ) {
		global $test_log_messages;

		// Store message in global array
		$test_log_messages[] = $message;

		// Also log to Logger instance
		\AthleteDashboard\Tests\Logger::getInstance()->log( $message );

		echo "\nBootstrap - error_log called with message: " . $message;

		return true;
	}
}

// Load wpdb class
require_once __DIR__ . '/php/class-wpdb.php';

// Load admin functions
require_once dirname( __DIR__ ) . '/includes/admin/functions.php';

// Initialize database connection
global $wpdb;
$wpdb = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );

// Create test tables if they don't exist
$wpdb->query(
	"CREATE TABLE IF NOT EXISTS {$wpdb->prefix}usermeta (
	umeta_id bigint(20) unsigned NOT NULL auto_increment,
	user_id bigint(20) unsigned NOT NULL default '0',
	meta_key varchar(255) default NULL,
	meta_value longtext,
	PRIMARY KEY (umeta_id),
	KEY user_id (user_id),
	KEY meta_key (meta_key(191))
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
);

// Define WordPress functions that we need for testing
if ( ! function_exists( 'wp_verify_nonce' ) ) {
	function wp_verify_nonce( $nonce, $action ) {
		return $nonce === 'valid_nonce';
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability, $user_id ) {
		global $current_user_capabilities;
		return isset( $current_user_capabilities[ $capability ] ) ? $current_user_capabilities[ $capability ] : false;
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return stripslashes_deep( $value );
	}
}

if ( ! function_exists( 'stripslashes_deep' ) ) {
	function stripslashes_deep( $value ) {
		return is_array( $value ) ? array_map( 'stripslashes_deep', $value ) : stripslashes( $value );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $str ) {
		return sanitize_text_field( $str );
	}
}

if ( ! function_exists( 'update_user_meta' ) ) {
	function update_user_meta( $user_id, $meta_key, $meta_value ) {
		global $wpdb;
		$meta_type = 'user';
		$table     = _get_meta_table( $meta_type );
		$column    = sanitize_key( $meta_type . '_id' );
		$id_column = 'user_id';

		$result = $wpdb->replace(
			$table,
			array(
				$id_column   => $user_id,
				'meta_key'   => $meta_key,
				'meta_value' => maybe_serialize( $meta_value ),
			),
			array(
				'%d',
				'%s',
				'%s',
			)
		);

		wp_cache_delete( $user_id, $meta_type . '_meta' );
		return (bool) $result;
	}
}

if ( ! function_exists( 'get_user_meta' ) ) {
	function get_user_meta( $user_id, $meta_key = '', $single = false ) {
		global $wpdb;
		$meta_type  = 'user';
		$table      = _get_meta_table( $meta_type );
		$id_column  = 'user_id';
		$query      = "SELECT meta_value FROM $table WHERE $id_column = ? AND meta_key = ?";
		$meta_value = $wpdb->get_var( $query, array( $user_id, $meta_key ) );
		return $single ? maybe_unserialize( $meta_value ) : array( $meta_value );
	}
}

if ( ! function_exists( '_get_meta_table' ) ) {
	function _get_meta_table( $type ) {
		global $wpdb;
		$table_name = $type . 'meta';
		return $wpdb->prefix . $table_name;
	}
}

if ( ! function_exists( 'wp_cache_delete' ) ) {
	function wp_cache_delete( $key, $group = '' ) {
		return true;
	}
}

if ( ! function_exists( 'maybe_serialize' ) ) {
	function maybe_serialize( $data ) {
		if ( is_array( $data ) || is_object( $data ) ) {
			return serialize( $data );
		}
		return $data;
	}
}

if ( ! function_exists( 'maybe_unserialize' ) ) {
	function maybe_unserialize( $data ) {
		if ( is_serialized( $data ) ) {
			return @unserialize( $data );
		}
		return $data;
	}
}

if ( ! function_exists( 'is_serialized' ) ) {
	function is_serialized( $data ) {
		if ( ! is_string( $data ) ) {
			return false;
		}
		$data = trim( $data );
		if ( 'N;' == $data ) {
			return true;
		}
		if ( ! preg_match( '/^([adObis]):/', $data, $badions ) ) {
			return false;
		}
		switch ( $badions[1] ) {
			case 'a':
			case 'O':
			case 's':
				if ( preg_match( "/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data ) ) {
					return true;
				}
				break;
			case 'b':
			case 'i':
			case 'd':
				if ( preg_match( "/^{$badions[1]}:[0-9.E-]+;\$/", $data ) ) {
					return true;
				}
				break;
		}
		return false;
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		$key = strtolower( $key );
		$key = preg_replace( '/[^a-z0-9_\-]/', '', $key );
		return $key;
	}
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( $action ) {
		return 'valid_nonce';
	}
}

if ( ! function_exists( 'checked' ) ) {
	function checked( $checked, $current = true, $echo = true ) {
		$result = $checked == $current ? ' checked="checked"' : '';
		if ( $echo ) {
			echo $result;
		}
		return $result;
	}
}

if ( ! function_exists( 'selected' ) ) {
	function selected( $selected, $current = true, $echo = true ) {
		$result = $selected == $current ? ' selected="selected"' : '';
		if ( $echo ) {
			echo $result;
		}
		return $result;
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_textarea' ) ) {
	function esc_textarea( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( '_e' ) ) {
	function _e( $text, $domain = 'default' ) {
		echo $text;
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $string, $remove_breaks = false ) {
		// First remove script and style tags and their contents
		$string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $string );
		// Remove any remaining script content that might be inline
		$string = preg_replace( '/\balert\s*\([^)]*\)/', '', $string );
		$string = strip_tags( $string );

		if ( $remove_breaks ) {
			$string = preg_replace( '/[\r\n\t ]+/', ' ', $string );
		}

		return trim( $string );
	}
}

// Load any additional test helpers
require_once __DIR__ . '/php/helpers.php';
