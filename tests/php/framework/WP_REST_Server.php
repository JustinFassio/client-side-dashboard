/**
 * Mock WP_REST_Server class for testing.
 *
 * @package AthleteDashboard\Tests\Framework
 */

namespace AthleteDashboard\Tests\Framework;

/**
 * Mock REST server class.
 */
class WP_REST_Server {
    /**
     * List of endpoints registered with the server.
     *
     * @var array
     */
    private $endpoints = array();

    /**
     * Dispatch a request to the server.
     *
     * @param WP_REST_Request $request Request to dispatch.
     * @return WP_REST_Response|WP_Error Response to the request.
     */
    public function dispatch( $request ) {
        // For now, just return a basic response.
        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    /**
     * Register a new route.
     *
     * @param string $namespace Namespace.
     * @param string $route    The REST route.
     * @param array  $args     Route arguments.
     * @return bool True on success, false on error.
     */
    public function register_route( $namespace, $route, $args = array() ) {
        $this->endpoints[] = array(
            'namespace' => $namespace,
            'route'     => $route,
            'args'      => $args,
        );
        return true;
    }

    /**
     * Get all registered routes.
     *
     * @return array Array of registered routes.
     */
    public function get_routes() {
        return $this->endpoints;
    }
} 