<?php
/**
 * Bootstrap file for Workout Generator feature
 */

class Workout_Generator_Bootstrap {
    /**
     * Configuration defaults
     */
    private const DEFAULTS = [
        'ai_service_endpoint' => 'http://localhost:3000',
        'rate_limit' => 100,
        'rate_window' => 3600,
        'debug_mode' => false
    ];

    /**
     * Initialize the feature
     */
    public function init() {
        // Load dependencies
        $this->load_dependencies();

        // Set up configuration
        $this->setup_configuration();

        // Register REST API endpoints
        add_action('rest_api_init', [$this, 'register_endpoints']);

        // Register scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);

        // Add admin settings
        if (is_admin()) {
            add_action('admin_init', [$this, 'register_settings']);
            add_action('admin_menu', [$this, 'add_settings_page']);
        }
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once __DIR__ . '/api/class-workout-endpoints.php';
        require_once __DIR__ . '/api/class-ai-service.php';
        require_once __DIR__ . '/api/class-workout-validator.php';
        require_once __DIR__ . '/api/class-rate-limiter.php';
    }

    /**
     * Set up configuration
     */
    private function setup_configuration() {
        // Get settings from options
        $settings = get_option('workout_generator_settings', []);

        // Define constants if not already defined
        if (!defined('AI_SERVICE_ENDPOINT')) {
            define('AI_SERVICE_ENDPOINT', 
                $settings['endpoint'] ?? self::DEFAULTS['ai_service_endpoint']
            );
        }

        if (!defined('AI_SERVICE_RATE_LIMIT')) {
            define('AI_SERVICE_RATE_LIMIT',
                $settings['rate_limit'] ?? self::DEFAULTS['rate_limit']
            );
        }

        if (!defined('AI_SERVICE_RATE_WINDOW')) {
            define('AI_SERVICE_RATE_WINDOW',
                $settings['rate_window'] ?? self::DEFAULTS['rate_window']
            );
        }

        // Set debug mode
        if (!defined('WORKOUT_GENERATOR_DEBUG')) {
            define('WORKOUT_GENERATOR_DEBUG',
                $settings['debug_mode'] ?? self::DEFAULTS['debug_mode']
            );
        }

        // Validate API key
        if (!defined('AI_SERVICE_API_KEY') || empty(AI_SERVICE_API_KEY)) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>';
                echo 'Workout Generator: AI service API key not configured. ';
                echo 'Please set the <code>AI_SERVICE_API_KEY</code> constant in your wp-config.php file.';
                echo '</p></div>';
            });
        }
    }

    /**
     * Register REST API endpoints
     */
    public function register_endpoints() {
        $endpoints = new Workout_Endpoints();
        $endpoints->register_routes();
    }

    /**
     * Register scripts and styles
     */
    public function register_assets() {
        // Only load on dashboard page
        if (!is_page('dashboard')) {
            return;
        }

        // Register TypeScript bundle
        wp_enqueue_script(
            'workout-generator',
            get_stylesheet_directory_uri() . '/features/workout-generator/dist/bundle.js',
            ['wp-api', 'react', 'react-dom'],
            filemtime(get_stylesheet_directory() . '/features/workout-generator/dist/bundle.js'),
            true
        );

        // Pass configuration to JavaScript
        wp_localize_script('workout-generator', 'workoutGeneratorConfig', [
            'apiEndpoint' => esc_url_raw(rest_url('athlete-dashboard/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
            'aiServiceEndpoint' => AI_SERVICE_ENDPOINT,
            'debug' => WORKOUT_GENERATOR_DEBUG,
            'rateLimit' => [
                'limit' => AI_SERVICE_RATE_LIMIT,
                'window' => AI_SERVICE_RATE_WINDOW
            ]
        ]);
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('workout_generator', 'workout_generator_settings', [
            'type' => 'array',
            'description' => 'Workout Generator settings',
            'sanitize_callback' => [$this, 'sanitize_settings'],
            'default' => self::DEFAULTS
        ]);

        add_settings_section(
            'workout_generator_main',
            'Workout Generator Settings',
            [$this, 'settings_section_callback'],
            'workout_generator'
        );

        add_settings_field(
            'endpoint',
            'AI Service Endpoint',
            [$this, 'endpoint_field_callback'],
            'workout_generator',
            'workout_generator_main'
        );

        add_settings_field(
            'rate_limit',
            'Rate Limit',
            [$this, 'rate_limit_field_callback'],
            'workout_generator',
            'workout_generator_main'
        );

        add_settings_field(
            'debug_mode',
            'Debug Mode',
            [$this, 'debug_mode_field_callback'],
            'workout_generator',
            'workout_generator_main'
        );
    }

    /**
     * Add settings page
     */
    public function add_settings_page() {
        add_options_page(
            'Workout Generator Settings',
            'Workout Generator',
            'manage_options',
            'workout_generator',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('workout_generator');
                do_settings_sections('workout_generator');
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>Configure the Workout Generator settings below.</p>';
    }

    /**
     * Endpoint field callback
     */
    public function endpoint_field_callback() {
        $settings = get_option('workout_generator_settings', self::DEFAULTS);
        ?>
        <input type="url" name="workout_generator_settings[endpoint]" 
               value="<?php echo esc_attr($settings['endpoint'] ?? self::DEFAULTS['ai_service_endpoint']); ?>"
               class="regular-text">
        <p class="description">The URL of your AI service endpoint.</p>
        <?php
    }

    /**
     * Rate limit field callback
     */
    public function rate_limit_field_callback() {
        $settings = get_option('workout_generator_settings', self::DEFAULTS);
        ?>
        <input type="number" name="workout_generator_settings[rate_limit]" 
               value="<?php echo esc_attr($settings['rate_limit'] ?? self::DEFAULTS['rate_limit']); ?>"
               min="1" max="1000" step="1">
        <span>requests per</span>
        <input type="number" name="workout_generator_settings[rate_window]" 
               value="<?php echo esc_attr($settings['rate_window'] ?? self::DEFAULTS['rate_window']); ?>"
               min="60" max="86400" step="60">
        <span>seconds</span>
        <p class="description">Maximum number of API requests allowed per time window.</p>
        <?php
    }

    /**
     * Debug mode field callback
     */
    public function debug_mode_field_callback() {
        $settings = get_option('workout_generator_settings', self::DEFAULTS);
        ?>
        <label>
            <input type="checkbox" name="workout_generator_settings[debug_mode]" 
                   value="1" <?php checked($settings['debug_mode'] ?? self::DEFAULTS['debug_mode']); ?>>
            Enable debug mode
        </label>
        <p class="description">Log API requests and responses for debugging.</p>
        <?php
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = [];

        // Sanitize endpoint URL
        if (isset($input['endpoint'])) {
            $sanitized['endpoint'] = esc_url_raw($input['endpoint']);
        }

        // Sanitize rate limit
        if (isset($input['rate_limit'])) {
            $sanitized['rate_limit'] = absint($input['rate_limit']);
            if ($sanitized['rate_limit'] < 1) {
                $sanitized['rate_limit'] = self::DEFAULTS['rate_limit'];
            }
        }

        // Sanitize rate window
        if (isset($input['rate_window'])) {
            $sanitized['rate_window'] = absint($input['rate_window']);
            if ($sanitized['rate_window'] < 60) {
                $sanitized['rate_window'] = self::DEFAULTS['rate_window'];
            }
        }

        // Sanitize debug mode
        $sanitized['debug_mode'] = !empty($input['debug_mode']);

        return $sanitized;
    }
}

// Initialize the feature
$workout_generator = new Workout_Generator_Bootstrap();
$workout_generator->init(); 