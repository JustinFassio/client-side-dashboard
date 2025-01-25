<?php
/**
 * Test helpers for the Athlete Dashboard theme
 */

if ( ! class_exists( 'WP_REST_Response' ) ) {
	/**
	 * Mock WP_REST_Response class if not available
	 */
	class WP_REST_Response {
		private $data;
		private $status;
		private $headers = array();

		public function __construct( $data = null, $status = 200 ) {
			$this->data   = $data;
			$this->status = $status;
		}

		public function get_data() {
			return $this->data;
		}

		public function get_status() {
			return $this->status;
		}

		public function header( $key, $value ) {
			$this->headers[ $key ] = $value;
			return $this;
		}

		public function get_headers() {
			return $this->headers;
		}
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Mock WP_Error class if not available
	 */
	class WP_Error {
		private $code;
		private $message;
		private $data;

		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		public function get_error_code() {
			return $this->code;
		}

		public function get_error_message() {
			return $this->message;
		}

		public function get_error_data() {
			return $this->data;
		}
	}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
	/**
	 * Mock WP_REST_Request class if not available
	 */
	class WP_REST_Request {
		private $method;
		private $route;
		private $params  = array();
		private $headers = array();
		private $body    = null;

		public function __construct( $method = '', $route = '' ) {
			$this->method = $method;
			$this->route  = $route;
		}

		public function get_method() {
			return $this->method;
		}

		public function get_route() {
			return $this->route;
		}

		public function set_param( $key, $value ) {
			$this->params[ $key ] = $value;
			return $this;
		}

		public function get_param( $key ) {
			return $this->params[ $key ] ?? null;
		}

		public function get_params() {
			return $this->params;
		}

		public function set_header( $key, $value ) {
			$this->headers[ $key ] = $value;
			return $this;
		}

		public function get_header( $key ) {
			return $this->headers[ $key ] ?? null;
		}

		public function get_headers() {
			return $this->headers;
		}

		public function set_body( $body ) {
			$this->body = $body;
			return $this;
		}

		public function get_body() {
			return $this->body;
		}

		public function get_json_params() {
			return is_string( $this->body ) ? json_decode( $this->body, true ) : $this->body;
		}
	}
}

if ( ! class_exists( 'WP_REST_Server' ) ) {
	/**
	 * Mock WP_REST_Server class if not available
	 */
	class WP_REST_Server {
		const READABLE  = 'GET';
		const CREATABLE = 'POST';
		const EDITABLE  = 'PUT, PATCH';
		const DELETABLE = 'DELETE';
		const ALL       = 'GET, POST, PUT, PATCH, DELETE';
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Mock translation function if not available
	 */
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * Mock wp_json_encode function if not available
	 */
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Mock sanitize_text_field function if not available
	 */
	function sanitize_text_field( $str ) {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	/**
	 * Mock sanitize_email function if not available
	 */
	function sanitize_email( $email ) {
		return filter_var( $email, FILTER_SANITIZE_EMAIL );
	}
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * Mock absint function if not available
	 */
	function absint( $number ) {
		return abs( (int) $number );
	}
}

if ( ! function_exists( 'rest_ensure_response' ) ) {
	/**
	 * Mock rest_ensure_response function if not available
	 */
	function rest_ensure_response( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		return new WP_REST_Response( $response );
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	/**
	 * Mock is_wp_error function if not available
	 */
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	/**
	 * Mock wp_parse_args function if not available
	 */
	function wp_parse_args( $args, $defaults = array() ) {
		if ( is_object( $args ) ) {
			$parsed_args = get_object_vars( $args );
		} elseif ( is_array( $args ) ) {
			$parsed_args = $args;
		} else {
			parse_str( $args, $parsed_args );
		}

		if ( is_array( $defaults ) && $defaults ) {
			return array_merge( $defaults, $parsed_args );
		}
		return $parsed_args;
	}
}
