# Athlete Dashboard API Documentation

## Overview
The Athlete Dashboard API provides a comprehensive interface for managing athlete profiles, training programs, and workout data. This API follows REST principles and implements robust security measures including rate limiting and request validation.

## Documentation Sections

1. [Getting Started](./GETTING_STARTED.md)
   - Authentication
   - Base URLs
   - Request/Response Format

2. [Rate Limiting & Validation](./RATE_LIMITING_AND_VALIDATION.md)
   - Rate Limiting Overview
   - Request Validation
   - Customization Guide
   - FAQ & Troubleshooting

3. [Endpoints](./ENDPOINTS.md)
   - Profile Management
   - Training Programs
   - Workout Data
   - Analytics

4. [Error Handling](./ERROR_HANDLING.md)
   - Error Codes
   - Error Messages
   - Debugging Guide

## Quick Start

### Authentication
All API requests require authentication using WordPress nonces:
```php
$nonce = wp_create_nonce('wp_rest');
```

Include the nonce in your request headers:
```php
'X-WP-Nonce': $nonce
```

### Rate Limits
The API implements rate limiting to prevent abuse. Default limits:
- Standard endpoints: 100 requests/hour
- Bulk operations: 20 requests/hour
- Global limit: 1000 requests/hour

For detailed information about rate limiting and how to customize it, see the [Rate Limiting & Validation Guide](./RATE_LIMITING_AND_VALIDATION.md).

### Basic Example
```php
// Get profile data
$response = wp_remote_get(
    rest_url('athlete-dashboard/v1/profile/123'),
    array(
        'headers' => array(
            'X-WP-Nonce' => wp_create_nonce('wp_rest')
        )
    )
);

// Update profile
$response = wp_remote_post(
    rest_url('athlete-dashboard/v1/profile/123'),
    array(
        'headers' => array(
            'X-WP-Nonce' => wp_create_nonce('wp_rest'),
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode(array(
            'firstName' => 'John',
            'lastName' => 'Doe'
        ))
    )
);
```

## Best Practices

1. **Rate Limiting**
   - Monitor rate limit headers
   - Implement exponential backoff
   - Use bulk endpoints when possible

2. **Validation**
   - Validate data client-side
   - Handle validation errors gracefully
   - Follow the validation rules in the documentation

3. **Error Handling**
   - Check response status codes
   - Log error messages
   - Implement proper error recovery

## Need Help?

- Review the [FAQ](./RATE_LIMITING_AND_VALIDATION.md#faq)
- Check error responses for detailed messages
- Contact support with specific error codes 