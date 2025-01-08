<?php
namespace AthleteDashboard\Config;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cache configuration settings
 */
return [
    // TTL (Time To Live) settings in seconds
    'ttl' => [
        'default' => 3600,        // 1 hour
        'profile' => 3600,        // 1 hour
        'overview' => 1800,       // 30 minutes
        'preferences' => 1800,    // 30 minutes
        'goals' => 3600,         // 1 hour
        'activity' => 900,       // 15 minutes
    ],

    // Cache warming settings
    'warm_cache' => [
        'enabled' => true,
        'on_login' => true,
        'on_cron' => true,
        'priority_users' => true, // Warm cache for users with recent activity
        'max_users_per_job' => 50,
        'activity_threshold' => 24 * HOUR_IN_SECONDS, // Consider users active if they've logged in within 24 hours
    ],

    // Cache groups to warm
    'warm_groups' => [
        'profile' => [
            'full',
            'meta',
            'preferences'
        ],
        'overview' => [
            'stats',
            'activity',
            'goals'
        ]
    ],

    // Cron schedule for cache warming
    'cron' => [
        'warm_cache' => 'fifteen_minutes',
        'cleanup' => 'daily',
    ],

    // Monitoring settings
    'monitoring' => [
        'enabled' => true,
        'log_stats' => true,
        'alert_thresholds' => [
            'hit_rate' => 0.8,           // Alert if hit rate falls below 80%
            'miss_rate' => 0.2,          // Alert if miss rate exceeds 20%
            'memory_usage' => 0.9,       // Alert if memory usage exceeds 90%
            'response_time' => 500,      // Alert if average response time exceeds 500ms
        ],
        'stats_retention' => 7,          // Days to keep monitoring stats
        'sampling_rate' => 0.1,          // Sample 10% of requests for detailed monitoring
        'alert_channels' => [
            'email' => true,
            'slack' => false,
            'admin_notice' => true,
            'log' => true,
        ],
        'alert_cooldown' => 3600,        // Minimum time between alerts (1 hour)
        'alert_recipients' => [
            'email' => [],               // Array of email addresses
            'slack_webhook' => '',       // Slack webhook URL
        ]
    ]
]; 