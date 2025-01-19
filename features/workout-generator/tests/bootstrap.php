<?php
/**
 * PHPUnit bootstrap file for Workout Generator tests
 */

// Initialize globals before function declarations
global $wp_filter, $wp_options, $wp_object_cache, $wp_transients, $wp_registered_settings, $wp_settings_sections, $wp_settings_fields, $wp_scripts, $current_screen, $wp_rest_server, $wp_query, $current_user;
$wp_filter = [];
$wp_options = [];
$wp_object_cache = [];
$wp_transients = [];
$wp_registered_settings = [];
$wp_settings_sections = [];
$wp_settings_fields = [];
$wp_scripts = null;
$current_screen = null;
$wp_rest_server = null;
$wp_query = new stdClass();
$wp_query->is_page = false;
$current_user = new stdClass();
$current_user->roles = ['administrator'];

// Add global for tracking user roles in factory
if (!isset($GLOBALS['wp_test_factory_user_roles'])) {
    $GLOBALS['wp_test_factory_user_roles'] = [];
}

// Define WordPress function mocks in global namespace
if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        global $current_user;
        
        // Debug logging
        error_log("=== current_user_can check ===");
        error_log("Checking capability: " . $capability);
        error_log("Current user object: " . print_r($current_user, true));
        error_log("Current user roles: " . (isset($current_user->roles) ? implode(', ', $current_user->roles) : 'no roles'));
        
        if (!isset($current_user->roles) || !is_array($current_user->roles)) {
            error_log("No valid roles set - returning false");
            return false;
        }

        // Define strict capability to role mapping
        $capability_roles = [
            'manage_options' => ['administrator'],
            'edit_posts' => ['administrator', 'editor', 'author'],
            'read' => ['administrator', 'editor', 'author', 'subscriber']
        ];

        // If capability isn't defined, default to false
        if (!isset($capability_roles[$capability])) {
            error_log("Unknown capability '$capability' - returning false");
            return false;
        }

        // Check if any of the user's roles have this capability
        $has_capability = false;
        foreach ($current_user->roles as $role) {
            error_log("Checking role: " . $role);
            if (in_array($role, $capability_roles[$capability], true)) {
                error_log("Role '$role' has capability '$capability'");
                $has_capability = true;
                break;
            }
            error_log("Role '$role' does not have capability '$capability'");
        }

        error_log("Final capability check result for '$capability': " . ($has_capability ? 'true' : 'false'));
        error_log("=== end current_user_can check ===");
        return $has_capability;
    }
}

if (!function_exists('wp_set_current_user')) {
    function wp_set_current_user($user_id) {
        global $current_user, $current_user_id;
        
        error_log("=== wp_set_current_user ===");
        error_log("Setting user ID: " . $user_id);
        
        $current_user_id = $user_id;
        
        // Ensure current_user is initialized
        if (!isset($current_user)) {
            error_log("Initializing current_user object");
            $current_user = new stdClass();
        }
        
        // Reset roles to default
        $current_user->roles = ['subscriber'];
        error_log("Default roles set to: " . implode(', ', $current_user->roles));
        
        // Set roles based on factory creation
        if (isset($GLOBALS['wp_test_factory_user_roles'][$user_id])) {
            $current_user->roles = [$GLOBALS['wp_test_factory_user_roles'][$user_id]];
            error_log("Setting user roles from factory to: " . implode(', ', $current_user->roles));
        }
        
        error_log("Final current_user object: " . print_r($current_user, true));
        error_log("=== end wp_set_current_user ===");
    }
}

// Load Composer autoloader once
require_once dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

// Define WordPress function mocks in global namespace
if (!function_exists('add_filter')) {
    function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        global $wp_filter;
        if (!isset($wp_filter[$tag])) {
            $wp_filter[$tag] = [];
        }
        $wp_filter[$tag][] = [
            'function' => $function_to_add,
            'priority' => $priority,
            'accepted_args' => $accepted_args
        ];
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value) {
        global $wp_filter;
        if (isset($wp_filter[$tag])) {
            foreach ($wp_filter[$tag] as $filter) {
                $value = call_user_func($filter['function'], $value);
            }
        }
        return $value;
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        global $wp_options;
        return $wp_options[$option] ?? $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) {
        global $wp_options;
        $wp_options[$option] = $value;
        return true;
    }
}

if (!function_exists('get_transient')) {
    function get_transient($key) {
        global $wp_transients;
        if (!isset($wp_transients)) {
            $wp_transients = [];
        }
        return $wp_transients[$key] ?? false;
    }
}

if (!function_exists('set_transient')) {
    function set_transient($key, $value, $expiration = 0) {
        global $wp_transients;
        if (!isset($wp_transients)) {
            $wp_transients = [];
        }
        $wp_transients[$key] = $value;
        return true;
    }
}

if (!function_exists('delete_transient')) {
    function delete_transient($key) {
        global $wp_transients;
        if (isset($wp_transients[$key])) {
            unset($wp_transients[$key]);
        }
        return true;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        global $current_user_id;
        return $current_user_id ?? 1;
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data) {
        return json_encode($data);
    }
}

if (!function_exists('get_bloginfo')) {
    function get_bloginfo() {
        return 'Test Site';
    }
}

if (!function_exists('wp_remote_request')) {
    function wp_remote_request($url, $args) {
        $response = apply_filters('pre_http_request', false, $args, $url);
        if ($response !== false) {
            return $response;
        }
        
        // Default mock response
        $mock_response = ['workout' => ['exercises' => []]];
        
        // Adjust response based on URL pattern
        if (strpos($url, '/workout/') !== false) {
            preg_match('/\/workout\/(\d+)/', $url, $matches);
            $mock_response = ['workout' => ['id' => $matches[1]]];
        } elseif (strpos($url, '/history/') !== false) {
            $mock_response = ['history' => []];
        } elseif (strpos($url, '/alternatives') !== false) {
            $mock_response = ['alternatives' => []];
        }
        
        return [
            'response' => ['code' => 200],
            'body' => json_encode($mock_response)
        ];
    }
}

if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response) {
        return $response['response']['code'];
    }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response) {
        return $response['body'];
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return $thing instanceof WP_Error;
    }
}

if (!function_exists('wp_cache_flush')) {
    function wp_cache_flush() {
        global $wp_object_cache;
        $wp_object_cache = [];
        return true;
    }
}

if (!function_exists('remove_all_filters')) {
    function remove_all_filters($tag) {
        global $wp_filter;
        if (isset($wp_filter[$tag])) {
            unset($wp_filter[$tag]);
        }
        return true;
    }
}

// Create WP_Error class if it doesn't exist
if (!class_exists('WP_Error')) {
    class WP_Error {
        private $code;
        private $message;
        private $data;

        public function __construct($code, $message, $data = null) {
            $this->code = $code;
            $this->message = $message;
            $this->data = $data;
        }

        public function get_error_message() {
            return $this->message;
        }

        public function get_error_data() {
            return $this->data;
        }
    }
}

// Create WP_Die_Exception class if it doesn't exist
if (!class_exists('WP_Die_Exception')) {
    class WP_Die_Exception extends Exception {}
}

// Create TestCase class if WP_UnitTestCase doesn't exist
if (!class_exists('WP_UnitTestCase')) {
    class WP_UnitTestCase extends \PHPUnit\Framework\TestCase {
        protected function setUp(): void {
            parent::setUp();
            
            // Set up WP_Die handler for testing
            \add_filter('wp_die_handler', function() {
                return function($message, $title = '', $args = []) {
                    throw new \WP_Die_Exception($message);
                };
            });
        }
    }
}

// Define required constants if not already defined
if (!defined('PHPUNIT_RUNNING')) {
    define('PHPUNIT_RUNNING', true);
}

if (!defined('AI_SERVICE_API_KEY')) {
    define('AI_SERVICE_API_KEY', 'test-api-key');
}

if (!defined('AI_SERVICE_ENDPOINT')) {
    define('AI_SERVICE_ENDPOINT', 'https://test-api.example.com');
}

// Define additional constants
if (!defined('AI_SERVICE_RATE_LIMIT')) {
    define('AI_SERVICE_RATE_LIMIT', 100);
}

if (!defined('AI_SERVICE_RATE_WINDOW')) {
    define('AI_SERVICE_RATE_WINDOW', 3600);
}

if (!defined('WORKOUT_GENERATOR_DEBUG')) {
    define('WORKOUT_GENERATOR_DEBUG', false);
}

// Load required files
require_once dirname(__DIR__) . '/api/class-ai-service.php';
require_once dirname(__DIR__) . '/api/class-rate-limiter.php';
require_once dirname(__DIR__) . '/api/class-workout-validator.php';
require_once dirname(__DIR__) . '/api/class-workout-endpoints.php';
require_once dirname(__DIR__) . '/src/class-workout-generator-bootstrap.php';

// Initialize class aliases for testing
class_alias('AthleteDashboard\Features\WorkoutGenerator\API\Workout_Endpoints', 'Workout_Endpoints');
class_alias('AthleteDashboard\Features\WorkoutGenerator\API\AI_Service', 'AI_Service');
class_alias('AthleteDashboard\Features\WorkoutGenerator\API\Rate_Limiter', 'Rate_Limiter');
class_alias('AthleteDashboard\Features\WorkoutGenerator\API\Workout_Validator', 'Workout_Validator');

if (!function_exists('register_setting')) {
    function register_setting($option_group, $option_name, $args = []) {
        global $wp_registered_settings;
        if (!isset($wp_registered_settings)) {
            $wp_registered_settings = [];
        }
        $wp_registered_settings[$option_name] = $args;
    }
}

if (!function_exists('get_registered_settings')) {
    function get_registered_settings() {
        global $wp_registered_settings;
        return $wp_registered_settings ?? [];
    }
}

if (!function_exists('add_settings_section')) {
    function add_settings_section($id, $title, $callback, $page) {
        global $wp_settings_sections;
        if (!isset($wp_settings_sections)) {
            $wp_settings_sections = [];
        }
        $wp_settings_sections[$page][$id] = [
            'id' => $id,
            'title' => $title,
            'callback' => $callback
        ];
    }
}

if (!function_exists('add_settings_field')) {
    function add_settings_field($id, $title, $callback, $page, $section, $args = []) {
        global $wp_settings_fields;
        if (!isset($wp_settings_fields)) {
            $wp_settings_fields = [];
        }
        if (!isset($wp_settings_fields[$page])) {
            $wp_settings_fields[$page] = [];
        }
        if (!isset($wp_settings_fields[$page][$section])) {
            $wp_settings_fields[$page][$section] = [];
        }
        $wp_settings_fields[$page][$section][$id] = [
            'id' => $id,
            'title' => $title,
            'callback' => $callback,
            'args' => $args
        ];
    }
}

if (!function_exists('wp_enqueue_scripts')) {
    function wp_enqueue_scripts() {
        do_action('wp_enqueue_scripts');
    }
}

if (!function_exists('wp_script_is')) {
    function wp_script_is($handle, $list = 'enqueued') {
        global $wp_scripts;
        if (!isset($wp_scripts)) {
            $wp_scripts = new stdClass();
            $wp_scripts->registered = [];
            $wp_scripts->queue = [];
        }
        if ($list === 'enqueued') {
            return in_array($handle, $wp_scripts->queue);
        }
        return isset($wp_scripts->registered[$handle]);
    }
}

if (!function_exists('wp_scripts')) {
    function wp_scripts() {
        global $wp_scripts;
        if (!isset($wp_scripts)) {
            $wp_scripts = new class {
                public $queue = [];
                public $registered = [];
                private $data = [];

                public function get_data($handle, $key = null) {
                    if ($key === null) {
                        return $this->data[$handle] ?? null;
                    }
                    return $this->data[$handle][$key] ?? null;
                }

                public function add_data($handle, $key, $value) {
                    if (!isset($this->data[$handle])) {
                        $this->data[$handle] = [];
                    }
                    $this->data[$handle][$key] = $value;
                }

                public function localize($handle, $object_name, $l10n) {
                    $this->add_data($handle, 'data', "var $object_name = " . wp_json_encode($l10n) . ';');
                }
            };
        }
        return $wp_scripts;
    }
}

if (!function_exists('set_current_screen')) {
    function set_current_screen($screen = '') {
        global $current_screen;
        $current_screen = (object) ['id' => $screen];
    }
}

if (!function_exists('do_action')) {
    function do_action($tag, ...$args) {
        global $wp_filter;
        if (isset($wp_filter[$tag])) {
            foreach ($wp_filter[$tag] as $filter) {
                call_user_func_array($filter['function'], $args);
            }
        }
    }
}

if (!function_exists('rest_get_server')) {
    function rest_get_server() {
        global $wp_rest_server;
        if (!isset($wp_rest_server)) {
            $wp_rest_server = new class {
                private $routes = [];
                
                public function register_route($namespace, $route, $args) {
                    $full_route = '/' . trim($namespace, '/') . '/' . trim($route, '/');
                    $this->routes[$full_route] = $args;
                }
                
                public function get_routes() {
                    return $this->routes;
                }

                public function dispatch($request) {
                    return new WP_Error('rest_forbidden', 'Sorry, you are not allowed to do that.');
                }
            };
        }
        return $wp_rest_server;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return trim(strip_tags($str));
    }
}

if (!function_exists('plugins_url')) {
    function plugins_url($path = '', $plugin = '') {
        return 'http://example.com/wp-content/plugins/' . ltrim($path, '/');
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = [], $ver = false, $in_footer = false) {
        global $wp_scripts;
        if (!isset($wp_scripts)) {
            $wp_scripts = new stdClass();
            $wp_scripts->queue = [];
            $wp_scripts->registered = [];
        }
        $wp_scripts->queue[] = $handle;
        $wp_scripts->registered[$handle] = (object)[
            'handle' => $handle,
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver,
            'args' => $in_footer
        ];
    }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $object_name, $l10n) {
        global $wp_scripts;
        if (!isset($wp_scripts)) {
            $wp_scripts = new stdClass();
            $wp_scripts->queue = [];
            $wp_scripts->registered = [];
        }
        if (isset($wp_scripts->registered[$handle])) {
            $wp_scripts->registered[$handle]->data = "var $object_name = " . json_encode($l10n) . ";";
        }
    }
}

if (!function_exists('wp_die')) {
    function wp_die($message = '', $title = '', $args = []) {
        error_log("wp_die called with message: " . $message);
        throw new \WP_Die_Exception($message);
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1) {
        return 'test_nonce';
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) {
        return $nonce === 'test_nonce';
    }
}

if (!function_exists('check_ajax_referer')) {
    function check_ajax_referer($action = -1, $query_arg = false, $die = true) {
        return true;
    }
}

if (!function_exists('wp_send_json')) {
    function wp_send_json($response, $status_code = null) {
        return $response;
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null, $status_code = null) {
        return ['success' => false, 'data' => $data];
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null, $status_code = null) {
        return ['success' => true, 'data' => $data];
    }
}

// Create WP_UnitTest_Factory class if it doesn't exist
if (!class_exists('WP_UnitTest_Factory')) {
    class WP_UnitTest_Factory {
        public $user;
        
        public function __construct() {
            $this->user = new class {
                private $user_count = 0;
                
                public function create($args = []) {
                    $this->user_count++;
                    $user_id = $this->user_count;
                    
                    error_log("=== WP_UnitTest_Factory creating user ===");
                    error_log("User ID: " . $user_id);
                    error_log("Args: " . print_r($args, true));
                    
                    // Store the role for this user
                    if (isset($args['role'])) {
                        $GLOBALS['wp_test_factory_user_roles'][$user_id] = $args['role'];
                        error_log("Stored role '" . $args['role'] . "' for user " . $user_id);
                    }
                    
                    error_log("=== end WP_UnitTest_Factory creating user ===");
                    
                    return $user_id;
                }
            };
        }
    }
}

// Add or update WordPress function mocks
if (!function_exists('set_query_var')) {
    function set_query_var($var, $value) {
        global $wp_query;
        $wp_query->$var = $value;
    }
}

if (!function_exists('add_action')) {
    function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        add_filter($tag, $function_to_add, $priority, $accepted_args);
    }
}

if (!function_exists('register_rest_route')) {
    function register_rest_route($namespace, $route, $args) {
        $server = rest_get_server();
        $server->register_route($namespace, $route, $args);
        return true;
    }
}

// Add WordPress settings functions
if (!function_exists('settings_fields')) {
    function settings_fields($option_group) {
        echo sprintf('<input type="hidden" name="option_page" value="%s" />', esc_attr($option_group));
        echo '<input type="hidden" name="_wpnonce" value="' . wp_create_nonce($option_group . '-options') . '" />';
    }
}

if (!function_exists('do_settings_sections')) {
    function do_settings_sections($page) {
        global $wp_settings_sections, $wp_settings_fields;
        
        if (!isset($wp_settings_sections[$page])) {
            return;
        }

        foreach ((array) $wp_settings_sections[$page] as $section) {
            if ($section['title']) {
                echo "<h2>{$section['title']}</h2>\n";
            }
            if ($section['callback']) {
                call_user_func($section['callback'], $section);
            }
            if (!isset($wp_settings_fields) || !isset($wp_settings_fields[$page]) || !isset($wp_settings_fields[$page][$section['id']])) {
                continue;
            }
            echo '<table class="form-table" role="presentation">';
            do_settings_fields($page, $section['id']);
            echo '</table>';
        }
    }
}

if (!function_exists('do_settings_fields')) {
    function do_settings_fields($page, $section) {
        global $wp_settings_fields;
        
        if (!isset($wp_settings_fields[$page][$section])) {
            return;
        }

        foreach ((array) $wp_settings_fields[$page][$section] as $field) {
            echo '<tr>';
            if (!empty($field['title'])) {
                echo "<th scope='row'>{$field['title']}</th>";
            }
            echo '<td>';
            call_user_func($field['callback'], $field['args']);
            echo '</td>';
            echo '</tr>';
        }
    }
}

if (!function_exists('submit_button')) {
    function submit_button($text = 'Save Changes', $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null) {
        echo sprintf(
            '%s<input type="submit" name="%s" id="%s" class="button button-%s" value="%s"%s />%s',
            $wrap ? '<p class="submit">' : '',
            esc_attr($name),
            esc_attr($name),
            esc_attr($type),
            esc_attr($text),
            $other_attributes ? ' ' . $other_attributes : '',
            $wrap ? '</p>' : ''
        );
    }
}

// Add WP_REST_Request class
if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request {
        private $method;
        private $route;
        private $params = [];

        public function __construct($method, $route) {
            $this->method = $method;
            $this->route = $route;
        }

        public function get_route() {
            return $this->route;
        }

        public function get_method() {
            return $this->method;
        }

        public function set_param($key, $value) {
            $this->params[$key] = $value;
            return $this;
        }

        public function get_param($key) {
            return $this->params[$key] ?? null;
        }

        public function get_params() {
            return $this->params;
        }
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
} 