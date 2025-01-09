<?php
namespace AthleteDashboard\Config;

/**
 * API Endpoint Configuration
 * 
 * Defines standardized endpoint paths and configurations for the Athlete Dashboard API.
 */
class Endpoints {
    const NAMESPACE = 'athlete-dashboard/v1';

    /**
     * Authentication endpoints
     */
    const AUTH = [
        'login' => [
            'path' => '/auth/login',
            'methods' => ['POST'],
            'deprecated_paths' => ['/auth_login']
        ],
        'register' => [
            'path' => '/auth/register',
            'methods' => ['POST'],
            'deprecated_paths' => []
        ],
        'logout' => [
            'path' => '/auth/logout',
            'methods' => ['POST'],
            'deprecated_paths' => []
        ],
        'refresh-token' => [
            'path' => '/auth/refresh-token',
            'methods' => ['POST'],
            'deprecated_paths' => ['/auth/refresh_token']
        ]
    ];

    /**
     * Profile endpoints
     */
    const PROFILE = [
        'get' => [
            'path' => '/profile/{user_id}',
            'methods' => ['GET'],
            'deprecated_paths' => ['/profile/(?P<id>\d+)']
        ],
        'update' => [
            'path' => '/profile/{user_id}',
            'methods' => ['PUT'],
            'deprecated_paths' => []
        ],
        'bulk-update' => [
            'path' => '/profile/bulk-updates',
            'methods' => ['POST'],
            'deprecated_paths' => ['/profile/bulk', '/profile_bulk']
        ],
        'settings' => [
            'path' => '/profile/{user_id}/settings',
            'methods' => ['GET', 'PUT'],
            'deprecated_paths' => []
        ],
        'preferences' => [
            'path' => '/profile/{user_id}/preferences',
            'methods' => ['GET', 'PUT'],
            'deprecated_paths' => []
        ]
    ];

    /**
     * Overview endpoints
     */
    const OVERVIEW = [
        'get' => [
            'path' => '/overview/{user_id}',
            'methods' => ['GET'],
            'deprecated_paths' => []
        ],
        'goals' => [
            'path' => '/overview/goals/{goal_id}',
            'methods' => ['GET', 'PUT', 'DELETE'],
            'deprecated_paths' => []
        ],
        'activity' => [
            'path' => '/overview/activity/{activity_id}',
            'methods' => ['GET', 'DELETE'],
            'deprecated_paths' => []
        ],
        'stats' => [
            'path' => '/overview/{user_id}/stats',
            'methods' => ['GET'],
            'deprecated_paths' => ['/overview/(?P<user_id>\d+)/statistics']
        ]
    ];

    /**
     * Get the full path for an endpoint
     */
    public static function get_path(string $group, string $endpoint): string {
        $endpoints = constant("self::" . strtoupper($group));
        return isset($endpoints[$endpoint]) ? $endpoints[$endpoint]['path'] : '';
    }

    /**
     * Get allowed methods for an endpoint
     */
    public static function get_methods(string $group, string $endpoint): array {
        $endpoints = constant("self::" . strtoupper($group));
        return isset($endpoints[$endpoint]) ? $endpoints[$endpoint]['methods'] : [];
    }

    /**
     * Get deprecated paths for an endpoint
     */
    public static function get_deprecated_paths(string $group, string $endpoint): array {
        $endpoints = constant("self::" . strtoupper($group));
        return isset($endpoints[$endpoint]) ? $endpoints[$endpoint]['deprecated_paths'] : [];
    }

    /**
     * Check if a path is deprecated
     */
    public static function is_deprecated_path(string $path): bool {
        foreach ([self::AUTH, self::PROFILE, self::OVERVIEW] as $group) {
            foreach ($group as $endpoint) {
                if (in_array($path, $endpoint['deprecated_paths'])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get the new path for a deprecated path
     */
    public static function get_new_path(string $deprecated_path): ?string {
        foreach ([self::AUTH, self::PROFILE, self::OVERVIEW] as $group) {
            foreach ($group as $endpoint) {
                if (in_array($deprecated_path, $endpoint['deprecated_paths'])) {
                    return $endpoint['path'];
                }
            }
        }
        return null;
    }
} 