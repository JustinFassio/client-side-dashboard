# Error Handling Guide

## Table of Contents
- [Overview](#overview)
- [Error Response Format](#error-response-format)
- [Common Error Codes](#common-error-codes)
- [Handling Errors](#handling-errors)
- [Debugging Guide](#debugging-guide)
- [Best Practices](#best-practices)

## Overview

The Athlete Dashboard API uses a consistent error handling approach across all endpoints. Errors are returned with appropriate HTTP status codes and detailed error messages to help diagnose and resolve issues quickly.

## Error Response Format

All API errors follow this format:

```json
{
    "code": "error_code",
    "message": "Human-readable error message",
    "data": {
        "status": 400,
        "errors": {
            "field_name": "Field-specific error message"
        }
    }
}
```

### Fields Explained

| Field | Description |
|-------|-------------|
| code | Machine-readable error code |
| message | Human-readable error description |
| data.status | HTTP status code |
| data.errors | Field-specific validation errors |

## Common Error Codes

### Authentication Errors (401, 403)

| Code | Description | Resolution |
|------|-------------|------------|
| invalid_credentials | Invalid or missing authentication | Check nonce is valid and included |
| session_expired | Authentication session expired | Re-authenticate user |
| permission_denied | User lacks required permissions | Check user roles and capabilities |

### Validation Errors (400)

| Code | Description | Resolution |
|------|-------------|------------|
| validation_failed | Request data validation failed | Check field requirements |
| invalid_format | Data format is incorrect | Verify data types and formats |
| missing_required | Required fields missing | Include all required fields |

### Rate Limiting Errors (429)

| Code | Description | Resolution |
|------|-------------|------------|
| rate_limit_exceeded | Too many requests | Check rate limit headers and wait |
| global_rate_limit_exceeded | Global rate limit reached | Implement request throttling |

### Resource Errors (404, 409)

| Code | Description | Resolution |
|------|-------------|------------|
| not_found | Resource does not exist | Verify resource ID |
| already_exists | Resource already exists | Check for duplicates |
| conflict | Resource state conflict | Resolve conflicting state |

### Server Errors (500)

| Code | Description | Resolution |
|------|-------------|------------|
| internal_error | Internal server error | Check server logs |
| service_unavailable | Service temporarily unavailable | Retry with exponential backoff |

## Handling Errors

### Client-Side Error Handling

```typescript
async function makeApiRequest() {
    try {
        const response = await fetch('/athlete-dashboard/v1/endpoint', {
            headers: {
                'X-WP-Nonce': nonce
            }
        });

        if (!response.ok) {
            const error = await response.json();
            handleApiError(error);
            return;
        }

        return await response.json();
    } catch (error) {
        handleNetworkError(error);
    }
}

function handleApiError(error) {
    switch (error.code) {
        case 'validation_failed':
            handleValidationErrors(error.data.errors);
            break;
        case 'rate_limit_exceeded':
            handleRateLimitError(error);
            break;
        case 'permission_denied':
            redirectToLogin();
            break;
        default:
            showErrorMessage(error.message);
    }
}
```

### Rate Limit Handling

```typescript
async function makeRequestWithRetry(url, options, maxRetries = 3) {
    for (let attempt = 1; attempt <= maxRetries; attempt++) {
        try {
            const response = await fetch(url, options);
            
            if (response.status === 429) {
                const retryAfter = response.headers.get('Retry-After');
                await delay(retryAfter * 1000);
                continue;
            }
            
            return response;
        } catch (error) {
            if (attempt === maxRetries) throw error;
            await delay(Math.pow(2, attempt) * 1000);
        }
    }
}
```

## Debugging Guide

### 1. Check Response Headers

Important headers to check:
```http
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 3600
X-WP-Debug: 1
```

### 2. Enable Debug Mode

Add to wp-config.php:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### 3. Check Error Logs

Default location:
```
wp-content/debug.log
```

### 4. Use Debug Endpoints

Development environments only:
```http
GET /athlete-dashboard/v1/debug/request
GET /athlete-dashboard/v1/debug/headers
```

## Best Practices

1. **Graceful Error Handling**
   - Always handle errors gracefully
   - Show user-friendly error messages
   - Log detailed errors for debugging

2. **Validation**
   - Validate data client-side before sending
   - Handle validation errors field by field
   - Show inline validation messages

3. **Rate Limiting**
   - Monitor rate limit headers
   - Implement exponential backoff
   - Cache responses when possible

4. **Security**
   - Never expose sensitive data in errors
   - Validate all user input
   - Use appropriate HTTP status codes

5. **Logging**
   - Log all API errors
   - Include request context
   - Monitor error patterns

## Common Debugging Scenarios

### 1. Authentication Issues

Check:
- Nonce validity
- User session status
- User permissions
- Request headers

### 2. Validation Failures

Check:
- Required fields
- Data types
- Field formats
- Request body structure

### 3. Rate Limiting

Check:
- Current rate limit status
- Reset time
- Request patterns
- Caching implementation

### 4. Performance Issues

Check:
- Response times
- Server load
- Database queries
- Cache hit rates

## Need Help?

1. Check the error response for detailed messages
2. Review server logs for additional context
3. Monitor rate limit headers
4. Contact support with:
   - Error code
   - Request details
   - Steps to reproduce
``` 