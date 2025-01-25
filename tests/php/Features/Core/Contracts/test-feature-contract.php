<?php
/**
 * Test Feature Contract
 *
 * @package AthleteDashboard\Tests\Features\Core\Contracts
 */

namespace AthleteDashboard\Tests\Features\Core\Contracts;

use AthleteDashboard\Features\Core\Contracts\Feature_Contract;
use AthleteDashboard\Features\Core\Contracts\Abstract_Feature;
use WP_UnitTestCase;

/**
 * Class Test_Feature_Contract
 */
class Test_Feature_Contract extends WP_UnitTestCase {
	/**
	 * Set up test environment.
	 */
	public function set_up() {
		parent::set_up();
		require_once dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) . '/features/core/contracts/interface-feature-contract.php';
		require_once dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) . '/features/core/contracts/class-abstract-feature.php';
	}

	/**
	 * Test that the contract interface exists and can be loaded.
	 */
	public function test_contract_exists() {
		$this->assertTrue(interface_exists(Feature_Contract::class));
	}

	/**
	 * Test that the abstract feature class exists and can be loaded.
	 */
	public function test_abstract_feature_exists() {
		$this->assertTrue(class_exists(Abstract_Feature::class));
	}

	/**
	 * Test that the abstract feature implements the contract.
	 */
	public function test_abstract_feature_implements_contract() {
		$reflection = new \ReflectionClass(Abstract_Feature::class);
		$this->assertTrue($reflection->implementsInterface(Feature_Contract::class));
	}
} 