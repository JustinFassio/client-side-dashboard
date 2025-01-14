<?php
/**
 * Profile transformer class.
 *
 * @package AthleteDashboard\Features\Profile\Data
 */

namespace AthleteDashboard\Features\Profile\Data;

/**
 * Class for transforming profile data between storage and API formats.
 */
class Profile_Transformer {
	/**
	 * Transform raw profile data to API format.
	 *
	 * @param array $data Raw profile data.
	 * @return array Transformed data.
	 */
	public function to_api( array $data ): array {
		return array(
			'age'              => isset( $data['age'] ) ? (int) $data['age'] : 0,
			'height'           => isset( $data['height'] ) ? (float) $data['height'] : 0.0,
			'weight'           => isset( $data['weight'] ) ? (float) $data['weight'] : 0.0,
			'gender'           => $data['gender'] ?? '',
			'fitnessLevel'     => $data['fitnessLevel'] ?? '',
			'activityLevel'    => $data['activityLevel'] ?? '',
			'preferredUnits'   => $data['preferredUnits'] ?? 'metric',
			'emergencyContact' => array(
				'name'  => $data['emergencyContactName'] ?? '',
				'phone' => $data['emergencyContactPhone'] ?? '',
			),
		);
	}

	/**
	 * Transform API data to storage format.
	 *
	 * @param array $data API format data.
	 * @return array Storage format data.
	 */
	public function to_storage( array $data ): array {
		$emergency_contact = $data['emergencyContact'] ?? array();

		return array(
			'age'                   => (int) ( $data['age'] ?? 0 ),
			'height'                => (float) ( $data['height'] ?? 0.0 ),
			'weight'                => (float) ( $data['weight'] ?? 0.0 ),
			'gender'                => $data['gender'] ?? '',
			'fitnessLevel'          => $data['fitnessLevel'] ?? '',
			'activityLevel'         => $data['activityLevel'] ?? '',
			'preferredUnits'        => $data['preferredUnits'] ?? 'metric',
			'emergencyContactName'  => $emergency_contact['name'] ?? '',
			'emergencyContactPhone' => $emergency_contact['phone'] ?? '',
		);
	}
}
