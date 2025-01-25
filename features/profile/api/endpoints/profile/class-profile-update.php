<?php
/**
 * Profile Update Endpoint.
 *
 * @package AthleteDashboard\Features\Profile\API\Endpoints\Profile
 */

namespace AthleteDashboard\Features\Profile\API\Endpoints\Profile;

use AthleteDashboard\Features\Profile\API\Endpoints\Base\Base_Endpoint;
use AthleteDashboard\Features\Profile\API\Endpoints\Base\Auth_Checks;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Class Profile_Update
 *
 * Handles POST/PUT requests for updating profile data.
 */
class Profile_Update extends Base_Endpoint {
	use Auth_Checks;

	/**
	 * Field validation rules.
	 *
	 * @var array
	 */
	private $field_validators;

	public function __construct() {
		$this->field_validators = array(
			'email'       => array(
				'sanitize' => 'sanitize_email',
				'validate' => 'is_email',
			),
			'firstName'   => array(
				'sanitize' => 'sanitize_text_field',
				'length'   => array(
					'min' => 1,
					'max' => 50,
				),
			),
			'lastName'    => array(
				'sanitize' => 'sanitize_text_field',
				'length'   => array(
					'min' => 1,
					'max' => 50,
				),
			),
			'displayName' => array(
				'sanitize' => 'sanitize_text_field',
				'length'   => array(
					'min' => 1,
					'max' => 100,
				),
			),
		);
	}

	/**
	 * Get the endpoint's REST route.
	 *
	 * @return string Route path relative to the base.
	 */
	public function get_route(): string {
		return '/' . $this->rest_base;
	}

	/**
	 * Get the endpoint's HTTP method.
	 *
	 * @return string HTTP method.
	 */
	public function get_method(): string {
		return WP_REST_Server::EDITABLE;
	}

	/**
	 * Get endpoint arguments for request validation.
	 *
	 * @return array Endpoint arguments.
	 */
	protected function get_endpoint_args(): array {
		return array(
			'firstName'   => array(
				'type'        => 'string',
				'description' => __( 'User first name.', 'athlete-dashboard' ),
				'required'    => false,
			),
			'lastName'    => array(
				'type'        => 'string',
				'description' => __( 'User last name.', 'athlete-dashboard' ),
				'required'    => false,
			),
			'displayName' => array(
				'type'        => 'string',
				'description' => __( 'User display name.', 'athlete-dashboard' ),
				'required'    => false,
			),
			'email'       => array(
				'type'        => 'string',
				'format'      => 'email',
				'description' => __( 'User email address.', 'athlete-dashboard' ),
				'required'    => false,
			),
		);
	}

	/**
	 * Handle the update request for profile data.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function handle_request( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		try {
			$user_id = $this->get_current_user_id();

			// Run auth checks
			$auth_result = $this->run_auth_checks(
				array(
					array( 'method' => 'check_logged_in' ),
					array(
						'method' => 'check_resource_owner',
						'args'   => array( $user_id ),
					),
					array(
						'method' => 'verify_nonce',
						'args'   => array( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' ),
					),
				)
			);

			if ( is_wp_error( $auth_result ) ) {
				return $this->error(
					$auth_result->get_error_message(),
					$auth_result->get_error_data()['status']
				);
			}

			// Get and validate update data
			$update_data = $this->get_update_data_from_request( $request );
			if ( empty( $update_data ) ) {
				return $this->error(
					__( 'No valid update data provided.', 'athlete-dashboard' ),
					400
				);
			}

			// Validate the data
			$validation_result = $this->validate_update_data( $update_data );
			if ( is_wp_error( $validation_result ) ) {
				return $this->validation_error( $validation_result );
			}

			// Update profile using service
			$updated_profile = $this->service->update_profile( $user_id, $update_data );
			if ( is_wp_error( $updated_profile ) ) {
				return $this->error(
					$updated_profile->get_error_message(),
					$updated_profile->get_error_data()['status'] ?? 500
				);
			}

			$response = $this->success( array( 'profile' => $updated_profile ), 201 );
			return $this->add_response_headers( $response );
		} catch ( \Exception $e ) {
			return $this->error(
				__( 'Failed to update profile data.', 'athlete-dashboard' ),
				500,
				array( 'error' => $e->getMessage() )
			);
		}
	}

	/**
	 * Add response headers for the update operation.
	 *
	 * @param WP_REST_Response $response Response object.
	 * @return WP_REST_Response Modified response object.
	 */
	protected function add_response_headers( WP_REST_Response $response ): WP_REST_Response {
		$response->header( 'Location', rest_url( $this->namespace . $this->get_route() ) );
		return $response;
	}

	/**
	 * Validate update data against defined rules.
	 *
	 * @param array $data Data to validate.
	 * @return true|WP_Error True if valid, WP_Error if not.
	 */
	private function validate_update_data( array $data ): true|WP_Error {
		$errors = new WP_Error();

		foreach ( $data as $field => $value ) {
			if ( ! isset( $this->field_validators[ $field ] ) ) {
				continue;
			}

			$rules = $this->field_validators[ $field ];

			// Apply sanitization
			if ( isset( $rules['sanitize'] ) && is_callable( $rules['sanitize'] ) ) {
				$data[ $field ] = call_user_func( $rules['sanitize'], $value );
			}

			// Apply validation
			if ( isset( $rules['validate'] ) && is_callable( $rules['validate'] ) ) {
				if ( ! call_user_func( $rules['validate'], $value ) ) {
					$errors->add(
						'invalid_' . $field,
						sprintf( __( 'Invalid %s provided.', 'athlete-dashboard' ), $field )
					);
				}
			}

			// Check length constraints
			if ( isset( $rules['length'] ) ) {
				$length = mb_strlen( $value );
				if ( isset( $rules['length']['min'] ) && $length < $rules['length']['min'] ) {
					$errors->add(
						'invalid_' . $field,
						sprintf(
							__( '%1$s must be at least %2$d characters.', 'athlete-dashboard' ),
							$field,
							$rules['length']['min']
						)
					);
				}
				if ( isset( $rules['length']['max'] ) && $length > $rules['length']['max'] ) {
					$errors->add(
						'invalid_' . $field,
						sprintf(
							__( '%1$s must not exceed %2$d characters.', 'athlete-dashboard' ),
							$field,
							$rules['length']['max']
						)
					);
				}
			}
		}

		return $errors->has_errors() ? $errors : true;
	}

	/**
	 * Get the schema for profile update data.
	 *
	 * @return array Schema data.
	 */
	public function get_schema(): array {
		if ( $this->schema ) {
			return $this->schema;
		}

		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'profile-update',
			'type'       => 'object',
			'properties' => array(
				'firstName'   => array(
					'type'        => 'string',
					'description' => __( 'User first name.', 'athlete-dashboard' ),
				),
				'lastName'    => array(
					'type'        => 'string',
					'description' => __( 'User last name.', 'athlete-dashboard' ),
				),
				'displayName' => array(
					'type'        => 'string',
					'description' => __( 'User display name.', 'athlete-dashboard' ),
				),
				'email'       => array(
					'type'        => 'string',
					'format'      => 'email',
					'description' => __( 'User email address.', 'athlete-dashboard' ),
				),
			),
		);

		return $this->schema;
	}

	/**
	 * Extract and validate update data from request.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array Validated update data.
	 */
	private function get_update_data_from_request( WP_REST_Request $request ): array {
		$update_data  = array();
		$valid_fields = array( 'firstName', 'lastName', 'displayName', 'email' );

		foreach ( $valid_fields as $field ) {
			$value = $request->get_param( $field );
			if ( $value !== null ) {
				$update_data[ $field ] = $value;
			}
		}

		return $update_data;
	}
}
