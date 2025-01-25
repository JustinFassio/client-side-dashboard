/**
 * Mock WP_Error class for testing.
 *
 * @package AthleteDashboard\Tests\Framework
 */

namespace AthleteDashboard\Tests\Framework;

/**
 * Mock error class.
 */
class WP_Error {
    /**
     * Error codes and messages.
     *
     * @var array
     */
    private $errors = array();

    /**
     * Error data.
     *
     * @var array
     */
    private $error_data = array();

    /**
     * Constructor.
     *
     * @param string|int $code    Error code.
     * @param string     $message Error message.
     * @param mixed     $data    Error data.
     */
    public function __construct( $code = '', $message = '', $data = '' ) {
        if ( empty( $code ) ) {
            return;
        }

        $this->errors[ $code ][] = $message;
        if ( ! empty( $data ) ) {
            $this->error_data[ $code ] = $data;
        }
    }

    /**
     * Get error codes.
     *
     * @return array Error codes.
     */
    public function get_error_codes() {
        return array_keys( $this->errors );
    }

    /**
     * Get error messages.
     *
     * @param string|int $code Optional. Error code to retrieve messages for.
     * @return array|string Error messages.
     */
    public function get_error_messages( $code = '' ) {
        if ( empty( $code ) ) {
            return array_reduce( $this->errors, 'array_merge', array() );
        }

        return isset( $this->errors[ $code ] ) ? $this->errors[ $code ] : array();
    }

    /**
     * Get error data.
     *
     * @param string|int $code Error code.
     * @return mixed Error data.
     */
    public function get_error_data( $code = '' ) {
        if ( empty( $code ) ) {
            $code = $this->get_error_codes();
            $code = array_shift( $code );
        }

        return isset( $this->error_data[ $code ] ) ? $this->error_data[ $code ] : null;
    }

    /**
     * Add an error.
     *
     * @param string|int $code    Error code.
     * @param string     $message Error message.
     * @param mixed     $data    Error data.
     */
    public function add( $code, $message, $data = '' ) {
        $this->errors[ $code ][] = $message;
        if ( ! empty( $data ) ) {
            $this->error_data[ $code ] = $data;
        }
    }
} 