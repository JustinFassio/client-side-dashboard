/**
 * Mock WP_REST_Response class for testing.
 *
 * @package AthleteDashboard\Tests\Framework
 */

namespace AthleteDashboard\Tests\Framework;

/**
 * Mock REST response class.
 */
class WP_REST_Response {
    /**
     * Response data.
     *
     * @var mixed
     */
    private $data;

    /**
     * Response status code.
     *
     * @var int
     */
    private $status;

    /**
     * Constructor.
     *
     * @param mixed $data   Response data.
     * @param int   $status Response status code.
     */
    public function __construct( $data = null, $status = 200 ) {
        $this->data = $data;
        $this->status = $status;
    }

    /**
     * Get response data.
     *
     * @return mixed Response data.
     */
    public function get_data() {
        return $this->data;
    }

    /**
     * Get response status code.
     *
     * @return int Response status code.
     */
    public function get_status() {
        return $this->status;
    }

    /**
     * Set response data.
     *
     * @param mixed $data Response data.
     */
    public function set_data( $data ) {
        $this->data = $data;
    }

    /**
     * Set response status code.
     *
     * @param int $status Response status code.
     */
    public function set_status( $status ) {
        $this->status = $status;
    }
} 