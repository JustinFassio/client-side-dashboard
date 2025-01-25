# Caching System Documentation

## Overview
The Athlete Dashboard implements a robust caching system with multiple layers to optimize performance and reduce server load. The system includes object caching, cache warming strategies, performance monitoring, and a real-time monitoring dashboard.

## Current Implementation

### Core Components

1. **Cache Service** (`includes/services/class-cache-service.php`)
   - Two-layer caching strategy using WordPress Object Cache and Transients
   - Default TTL of 1 hour for most cached items
   - Methods for getting, setting, and invalidating cache
   - Support for cache groups and namespacing

2. **Cache Warmer** (`includes/services/class-cache-warmer.php`)
   - Proactive cache population
   - Triggered on user login and via cron jobs
   - Prioritizes frequently accessed user data
   - Configurable warming strategies
   - 15-minute scheduled cache warming jobs
   - Priority-based user selection based on activity and program count

3. **Cache Monitor** (`includes/services/class-cache-monitor.php`)
   - Tracks cache performance metrics
   - Monitors hit rates and response times
   - Configurable alerting system
   - Performance statistics logging

4. **Cache Stats Dashboard** (`includes/admin/class-cache-stats-widget.php`)
   - Real-time cache performance visualization
   - Hit rate and response time monitoring
   - Error rate tracking
   - Historical performance charts
   - Recent cache jobs table
   - Responsive design for all screen sizes

### Configuration

The caching system is configured in `includes/config/cache-config.php` with the following settings:

```php
[
    'ttl' => [
        'default' => 3600,     // 1 hour
        'profile' => 3600,     // 1 hour
        'overview' => 1800,    // 30 minutes
        'preferences' => 1800, // 30 minutes
        'goals' => 3600,      // 1 hour
        'activity' => 900,    // 15 minutes
    ],
    'warm_cache' => [
        'enabled' => true,
        'on_login' => true,
        'on_cron' => true,
        'priority_users' => true,
        'max_users_per_job' => 50,
        'activity_threshold' => 24 * HOUR_IN_SECONDS
    ]
]
```

### Performance Monitoring

The Cache Stats Dashboard provides real-time insights into cache performance:

1. **Key Metrics**
   - Cache Hit Rate: Percentage of successful cache retrievals
   - Average Response Time: Mean duration of cache operations
   - Error Rate: Percentage of failed cache operations

2. **Performance Charts**
   - 24-hour historical view of cache performance
   - Duration of cache operations
   - Number of items warmed
   - Error counts

3. **Recent Jobs Table**
   - Timestamp of cache operations
   - Job type (priority_users, user_login)
   - Duration of each job
   - Number of users processed
   - Items warmed
   - Error counts

### Currently Cached Data

1. **Profile Data**
   - Basic user information
   - Profile preferences
   - User meta data
   - Profile statistics

2. **Overview Data**
   - User statistics
   - Recent activity
   - Goals and progress
   - Performance metrics

## Future Development Plans

### Phase 1: Enhanced Caching (In Progress)
- [x] Implement 15-minute cache warming schedule
- [x] Add priority-based user cache warming
- [x] Implement performance monitoring dashboard
- [ ] Implement distributed cache locking
- [ ] Add cache versioning system
- [ ] Implement cache tags for better invalidation
- [ ] Add cache compression for large objects

### Phase 2: Performance Optimization (On Hold)
- [ ] Implement fragment caching for partial content
- [ ] Add cache preloading for predictive scenarios
- [ ] Implement cache sharding for better distribution
- [ ] Add cache analytics and reporting

### Phase 3: Advanced Features (On Hold)
- [ ] Implement cache hierarchy system
- [ ] Add cache synchronization across servers
- [ ] Implement cache warming based on user patterns
- [ ] Add advanced cache debugging tools

## Current Limitations

1. **No Distributed Locking**
   - Potential race conditions in multi-server setups
   - No coordination between multiple cache writers

2. **Basic Invalidation Strategy**
   - Full cache group invalidation
   - No selective cache tag invalidation
   - Limited cache dependencies support

## Best Practices

1. **Cache Keys**
   ```php
   // Use descriptive, namespaced keys
   $key = Cache_Service::generate_user_key($user_id, 'profile');
   ```

2. **Cache Duration**
   ```php
   // Use appropriate TTL for data type
   Cache_Service::set($key, $data, Cache_Service::DEFAULT_EXPIRATION);
   ```

3. **Cache Invalidation**
   ```php
   // Invalidate specific user cache
   Cache_Service::invalidate_user_cache($user_id);
   ```

## Development Guidelines

1. **Adding New Cached Data**
   - Define clear cache keys
   - Set appropriate TTL
   - Document cache dependencies
   - Add cache warming support

2. **Cache Invalidation**
   - Identify all affected cache keys
   - Use group invalidation when appropriate
   - Consider cache dependencies
   - Document invalidation triggers

3. **Performance Monitoring**
   - Monitor cache hit rates
   - Track memory usage
   - Log cache operations
   - Set up alerts for issues

## Next Steps

1. **Enhance Monitoring**
   - Add real-time updates to dashboard widget
   - Implement alert thresholds for metrics
   - Add export functionality for logs
   - Enhance visualization options

2. **Optimize Cache Warming**
   - Fine-tune priority user selection
   - Implement progressive cache warming
   - Add failure recovery mechanisms
   - Optimize job scheduling

3. **Improve Documentation**
   - Add API examples for common operations
   - Create troubleshooting guide
   - Document best practices for custom implementations
   - Add performance optimization tips

## Need Help?

- Review error logs for cache-related issues
- Monitor cache performance in the dashboard widget
- Check cache hit rates and memory usage
- Contact development team for assistance 