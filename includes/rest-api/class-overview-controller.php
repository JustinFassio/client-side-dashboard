<?php
namespace AthleteDashboard\RestApi;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Overview_Controller extends WP_REST_Controller {
    /**
     * Constructor.
     */
    public function __construct() {
        $this->namespace = 'custom/v1';
        $this->rest_base = 'overview';
    }

    /**
     * Register routes.
     */
    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<user_id>\d+)', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_overview_data'],
                'permission_callback' => [$this, 'check_permission'],
                'args'               => $this->get_collection_params(),
            ]
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/goals/(?P<goal_id>\d+)', [
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'update_goal'],
                'permission_callback' => [$this, 'check_permission'],
                'args'               => [
                    'progress' => [
                        'required'          => true,
                        'type'             => 'integer',
                        'minimum'          => 0,
                        'maximum'          => 100,
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ]
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/activity/(?P<activity_id>\d+)', [
            [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => [$this, 'dismiss_activity'],
                'permission_callback' => [$this, 'check_permission'],
            ]
        ]);
    }

    /**
     * Check if the current user has permission.
     */
    public function check_permission($request) {
        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_forbidden',
                __('You must be logged in to access this endpoint.', 'athlete-dashboard'),
                ['status' => 401]
            );
        }

        $user_id = $request->get_param('user_id');
        if ($user_id && get_current_user_id() !== (int) $user_id) {
            return new WP_Error(
                'rest_forbidden',
                __('You can only access your own data.', 'athlete-dashboard'),
                ['status' => 403]
            );
        }

        return true;
    }

    /**
     * Get overview data for a user.
     */
    public function get_overview_data(WP_REST_Request $request): WP_REST_Response {
        $user_id = (int) $request->get_param('user_id');
        
        // Get workouts completed
        $workouts_completed = (int) get_user_meta($user_id, 'workouts_completed', true);
        
        // Get active programs
        $active_programs = $this->get_active_programs($user_id);
        
        // Get nutrition score
        $nutrition_score = $this->calculate_nutrition_score($user_id);
        
        // Get recent activity
        $recent_activity = $this->get_recent_activity($user_id);
        
        // Get goals
        $goals = $this->get_user_goals($user_id);

        $data = [
            'stats' => [
                'workouts_completed' => $workouts_completed,
                'active_programs' => count($active_programs),
                'nutrition_score' => $nutrition_score,
            ],
            'recent_activity' => $recent_activity,
            'goals' => $goals,
        ];

        return new WP_REST_Response($data, 200);
    }

    /**
     * Update a goal's progress.
     */
    public function update_goal(WP_REST_Request $request): WP_REST_Response {
        $goal_id = (int) $request->get_param('goal_id');
        $progress = (int) $request->get_param('progress');

        // Update goal progress in database
        update_post_meta($goal_id, 'goal_progress', $progress);

        return new WP_REST_Response(['success' => true], 200);
    }

    /**
     * Dismiss an activity.
     */
    public function dismiss_activity(WP_REST_Request $request): WP_REST_Response {
        $activity_id = (int) $request->get_param('activity_id');

        // Mark activity as dismissed in database
        update_post_meta($activity_id, 'activity_dismissed', true);

        return new WP_REST_Response(['success' => true], 200);
    }

    /**
     * Get active programs for a user.
     */
    private function get_active_programs(int $user_id): array {
        $args = [
            'post_type' => 'program',
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => 'program_user',
                    'value' => $user_id,
                ],
                [
                    'key' => 'program_status',
                    'value' => 'active',
                ],
            ],
        ];

        $query = new \WP_Query($args);
        return $query->posts;
    }

    /**
     * Calculate nutrition score for a user.
     */
    private function calculate_nutrition_score(int $user_id): int {
        // Implement nutrition score calculation logic
        $score = (int) get_user_meta($user_id, 'nutrition_score', true);
        return $score ?: 0;
    }

    /**
     * Get recent activity for a user.
     */
    private function get_recent_activity(int $user_id): array {
        $args = [
            'post_type' => 'activity',
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [
                [
                    'key' => 'activity_user',
                    'value' => $user_id,
                ],
                [
                    'key' => 'activity_dismissed',
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ];

        $query = new \WP_Query($args);
        $activities = [];

        foreach ($query->posts as $post) {
            $activities[] = [
                'id' => $post->ID,
                'type' => get_post_meta($post->ID, 'activity_type', true),
                'title' => $post->post_title,
                'date' => get_the_date('Y-m-d', $post),
            ];
        }

        return $activities;
    }

    /**
     * Get goals for a user.
     */
    private function get_user_goals(int $user_id): array {
        $args = [
            'post_type' => 'goal',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'goal_user',
                    'value' => $user_id,
                ],
            ],
        ];

        $query = new \WP_Query($args);
        $goals = [];

        foreach ($query->posts as $post) {
            $goals[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'progress' => (int) get_post_meta($post->ID, 'goal_progress', true),
                'target_date' => get_post_meta($post->ID, 'goal_target_date', true),
            ];
        }

        return $goals;
    }

    /**
     * Get collection parameters.
     */
    public function get_collection_params(): array {
        return [
            'user_id' => [
                'required'          => true,
                'type'             => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ],
        ];
    }
} 