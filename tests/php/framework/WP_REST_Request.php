/**
 * Mock WP_REST_Request class for testing.
 *
 * @package AthleteDashboard\Tests\Framework
 */

namespace AthleteDashboard\Tests\Framework;

/**
 * Mock REST request class.
 */
class WP_REST_Request {
    /**
     * Request method.
     *
     * @var string
     */
    private $method;

    /**
     * Request route.
     *
     * @var string
     */
    private $route;

    /**
     * Request parameters.
     *
     * @var array
     */
    private $params = array();

    /**
     * Constructor.
     *
     * @param string $method HTTP method.
     * @param string $route  Request route.
     */
    public function __construct( $method = 'GET', $route = '' ) {
        $this->method = $method;
        $this->route = $route;
    }

    /**
     * Get request method.
     *
     * @return string Request method.
     */
    public function get_method() {
        return $this->method;
    }

    /**
     * Get request route.
     *
     * @return string Request route.
     */
    public function get_route() {
        return $this->route;
    }

    /**
     * Set request parameters.
     *
     * @param array $params Request parameters.
     */
    public function set_body_params( $params ) {
        $this->params = $params;
    }

    /**
     * Get request parameters.
     *
     * @return array Request parameters.
     */
    public function get_body_params() {
        return $this->params;
    }
} 