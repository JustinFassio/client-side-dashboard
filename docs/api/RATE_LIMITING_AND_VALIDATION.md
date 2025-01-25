# Rate Limiting and Validation Guide

## Table of Contents
- [Rate Limiting](#rate-limiting)
  - [Overview](#rate-limiting-overview)
  - [Default Limits](#default-limits)
  - [Customization](#rate-limiting-customization)
  - [FAQ](#rate-limiting-faq)
- [Request Validation](#request-validation)
  - [Overview](#validation-overview)
  - [Built-in Rules](#built-in-rules)
  - [Customization](#validation-customization)
  - [FAQ](#validation-faq)

## Rate Limiting

### Rate Limiting Overview
The Athlete Dashboard API implements a dual-layer rate limiting system:
1. **Endpoint-specific limits**: Controls request frequency for individual endpoints
2. **Global limits**: Prevents abuse across all endpoints

Each request includes rate limit information in the response headers:
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 3600
```

### Default Limits
- Standard endpoints: 100 requests per hour
- Bulk operations: 20 requests per hour
- Global limit: 1000 requests per hour across all endpoints
- Admin users: Higher limits (configurable)

### Rate Limiting Customization

#### 1. Modifying Default Limits
Create a custom rate limiter configuration in your theme's `functions.php`:

```php
add_filter('athlete_dashboard_rate_limits', function($limits) {
    return [
        'profile' => [
            'limit' => 200,  // requests
            'window' => 3600 // seconds
        ],
        'bulk_operations' => [
            'limit' => 50,
            'window' => 7200
        ],
        'global' => [
            'limit' => 2000,
            'window' => 3600
        ]
    ];
});
```

#### 2. Role-Based Limits
Implement custom limits based on user roles:

```php
add_filter('athlete_dashboard_rate_limit_for_user', function($limit, $user_id, $endpoint) {
    $user = get_user_by('id', $user_id);
    
    if (in_array('administrator', $user->roles)) {
        return [
            'limit' => $limit['limit'] * 2,  // Double the limit for admins
            'window' => $limit['window']
        ];
    }
    
    return $limit;
}, 10, 3);
```

## Request Validation

### Validation Overview
The API implements comprehensive request validation with:
- Type checking
- Required field validation
- Pattern matching
- Range validation
- Custom validation rules
- Nested object validation
- Array validation

### Built-in Rules
Common validation rules are pre-configured for profile data:

```php
[
    'firstName' => [
        'type' => 'string',
        'required' => true,
        'pattern' => '/^[a-zA-Z\s\'-]+$/',
        'max_length' => 50
    ],
    'age' => [
        'type' => 'integer',
        'min' => 13,
        'max' => 120
    ]
    // ... more rules
]
```

### Validation Customization

#### 1. Adding Custom Rules
Create custom validation rules in your theme:

```php
add_filter('athlete_dashboard_validation_rules', function($rules) {
    $rules['custom_field'] = [
        'type' => 'string',
        'required' => true,
        'validate_callback' => function($value) {
            if (!your_custom_validation($value)) {
                return new WP_Error(
                    'validation_failed',
                    'Custom validation message'
                );
            }
            return true;
        }
    ];
    return $rules;
});
```

#### 2. Custom Type Validation
Implement validation for custom data types:

```php
add_filter('athlete_dashboard_validate_type', function($result, $value, $rule) {
    if ($rule['type'] === 'custom_type') {
        return your_custom_type_validation($value);
    }
    return $result;
}, 10, 3);
```

## FAQ

### Rate Limiting FAQ

**Q: Why am I getting "Rate limit exceeded" errors?**
A: You've exceeded either the endpoint-specific limit or the global limit. Check the `X-RateLimit-*` headers in the response to see your current limits and reset time.

**Q: Do rate limits apply to authenticated requests only?**
A: Yes, rate limits are tracked per user. Unauthenticated requests are blocked by default.

**Q: How can I handle rate limit errors gracefully?**
A: Check the `Retry-After` header in 429 responses to determine when to retry. Implement exponential backoff in your client code.

**Q: Can I request higher rate limits?**
A: Administrators can customize rate limits using the `athlete_dashboard_rate_limits` filter. Contact the system administrator.

### Validation FAQ

**Q: Why are my nested objects failing validation?**
A: Ensure all required fields are present and properly formatted. Check the error response for specific field paths (e.g., `profile.contact.email`).

**Q: How do I validate arrays of custom objects?**
A: Use the array validation syntax with nested object rules:
```php
'custom_objects' => [
    'type' => 'array',
    'items' => [
        'type' => 'object',
        'properties' => [
            // ... object properties
        ]
    ]
]
```

**Q: Can I implement custom sanitization?**
A: Yes, use the `validate_callback` property in your rules to implement custom sanitization:
```php
'field' => [
    'validate_callback' => function($value) {
        return your_custom_sanitization($value);
    }
]
```

**Q: How do I handle different validation rules for create vs. update?**
A: The validation context ('create' or 'update') is passed to the validator. Required fields are only enforced during creation by default.

## Best Practices

1. **Rate Limiting**
   - Implement client-side rate tracking
   - Use bulk endpoints for multiple operations
   - Cache responses when possible
   - Handle rate limit errors gracefully

2. **Validation**
   - Validate client-side before sending requests
   - Include proper error handling
   - Use type-safe request data
   - Implement proper sanitization

## Need Help?

For additional assistance:
- Check the error response for detailed messages
- Review the API logs for request details
- Contact the development team with specific error codes
- Submit issues through the support system 