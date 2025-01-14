<?php
/**
 * Mock PHPMailer class for testing.
 *
 * @package AthleteDashboard\Tests\Framework
 */

namespace AthleteDashboard\Tests\Framework;

/**
 * Mock PHPMailer class
 */
class MockPHPMailer {
	/**
	 * Array of sent emails
	 *
	 * @var array
	 */
	public $mock_sent = array();

	/**
	 * To addresses
	 *
	 * @var array
	 */
	public $to = array();

	/**
	 * CC addresses
	 *
	 * @var array
	 */
	public $cc = array();

	/**
	 * BCC addresses
	 *
	 * @var array
	 */
	public $bcc = array();

	/**
	 * Email subject
	 *
	 * @var string
	 */
	public $Subject = '';

	/**
	 * Email body
	 *
	 * @var string
	 */
	public $Body = '';

	/**
	 * Headers
	 *
	 * @var string
	 */
	public $Headers = '';

	/**
	 * Send the email
	 *
	 * @return bool
	 */
	public function send() {
		$this->mock_sent[] = array(
			'to'      => $this->to,
			'cc'      => $this->cc,
			'bcc'     => $this->bcc,
			'header'  => $this->Headers,
			'subject' => $this->Subject,
			'body'    => $this->Body,
		);

		return true;
	}

	/**
	 * Add a recipient
	 *
	 * @param string $address Email address.
	 * @param string $name    Name.
	 * @return bool
	 */
	public function addAddress( $address, $name = '' ) {
		$this->to[] = array( $address, $name );
		return true;
	}

	/**
	 * Add a CC recipient
	 *
	 * @param string $address Email address.
	 * @param string $name    Name.
	 * @return bool
	 */
	public function addCC( $address, $name = '' ) {
		$this->cc[] = array( $address, $name );
		return true;
	}

	/**
	 * Add a BCC recipient
	 *
	 * @param string $address Email address.
	 * @param string $name    Name.
	 * @return bool
	 */
	public function addBCC( $address, $name = '' ) {
		$this->bcc[] = array( $address, $name );
		return true;
	}

	/**
	 * Get a sent email
	 *
	 * @param int $index Email index.
	 * @return object|false
	 */
	public function get_sent( $index = 0 ) {
		if ( isset( $this->mock_sent[ $index ] ) ) {
			return (object) $this->mock_sent[ $index ];
		}
		return false;
	}
}
