# Getting Started with the Athlete Dashboard API

## Table of Contents
- [Introduction](#introduction)
- [Prerequisites](#prerequisites)
- [Authentication](#authentication)
- [Making Your First Request](#making-your-first-request)
- [Request/Response Format](#requestresponse-format)
- [Examples](#examples)
- [Next Steps](#next-steps)

## Introduction

The Athlete Dashboard API provides a RESTful interface for managing athlete profiles, training programs, and workout data. This guide will help you get started with using the API in your applications.

## Prerequisites

Before you begin, ensure you have:

1. A WordPress installation with the Athlete Dashboard theme
2. User credentials with appropriate permissions
3. Basic understanding of REST APIs and HTTP
4. Familiarity with your chosen programming language/framework

## Authentication

The API uses WordPress nonces for authentication. Here's how to authenticate your requests:

### 1. Get a Nonce

Using PHP:
```php
$nonce = wp_create_nonce('wp_rest');
```

Using JavaScript:
```javascript
const nonce = athleteDashboardData.nonce;
```

### 2. Include in Requests

Add the nonce to your request headers:
```http
X-WP-Nonce: your_nonce_here
```

### Example Authentication Flow

```php
// PHP Example
$response = wp_remote_get(
    rest_url('athlete-dashboard/v1/profile/123'),
    array(
        'headers' => array(
            'X-WP-Nonce' => wp_create_nonce('wp_rest')
        )
    )
);
```

```javascript
// JavaScript Example
fetch('/wp-json/athlete-dashboard/v1/profile/123', {
    headers: {
        'X-WP-Nonce': athleteDashboardData.nonce
    }
})
.then(response => response.json())
.then(data => console.log(data));
```

## Making Your First Request

Let's make a simple request to get a user's profile:

### Using cURL

```bash
curl -X GET \
  'https://your-site.com/wp-json/athlete-dashboard/v1/profile/123' \
  -H 'X-WP-Nonce: your_nonce_here'
```

### Using PHP

```php
$args = array(
    'headers' => array(
        'X-WP-Nonce' => wp_create_nonce('wp_rest')
    )
);

$response = wp_remote_get(
    rest_url('athlete-dashboard/v1/profile/123'),
    $args
);

if (!is_wp_error($response)) {
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);
    // Handle the data
}
```

### Using JavaScript

```javascript
async function getProfile(userId) {
    try {
        const response = await fetch(
            `/wp-json/athlete-dashboard/v1/profile/${userId}`,
            {
                headers: {
                    'X-WP-Nonce': athleteDashboardData.nonce
                }
            }
        );
        
        if (!response.ok) {
            throw new Error('API request failed');
        }
        
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error fetching profile:', error);
        throw error;
    }
}
```

## Request/Response Format

### Request Format

- Use JSON for request bodies
- Include proper headers:
  ```http
  Content-Type: application/json
  X-WP-Nonce: your_nonce_here
  ```
- Use query parameters for filtering/sorting

### Response Format

Successful response:
```json
{
    "data": {
        // Response data
    },
    "status": 200
}
```

Error response:
```json
{
    "code": "error_code",
    "message": "Error message",
    "data": {
        "status": 400
    }
}
```

## Examples

### Creating a Profile

```javascript
async function createProfile(profileData) {
    const response = await fetch('/wp-json/athlete-dashboard/v1/profile', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': athleteDashboardData.nonce
        },
        body: JSON.stringify(profileData)
    });
    
    return await response.json();
}

// Usage
const profile = await createProfile({
    firstName: 'John',
    lastName: 'Doe',
    email: 'john@example.com'
});
```

### Updating a Profile

```javascript
async function updateProfile(userId, updates) {
    const response = await fetch(
        `/wp-json/athlete-dashboard/v1/profile/${userId}`,
        {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': athleteDashboardData.nonce
            },
            body: JSON.stringify(updates)
        }
    );
    
    return await response.json();
}

// Usage
const updated = await updateProfile(123, {
    weight: 75,
    height: 180
});
```

### Error Handling

```javascript
async function makeApiRequest(endpoint, options = {}) {
    try {
        const response = await fetch(endpoint, {
            ...options,
            headers: {
                ...options.headers,
                'X-WP-Nonce': athleteDashboardData.nonce
            }
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message);
        }
        
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}
```

## Next Steps

1. Review the [API Endpoints](./ENDPOINTS.md) documentation
2. Learn about [Rate Limiting & Validation](./RATE_LIMITING_AND_VALIDATION.md)
3. Understand [Error Handling](./ERROR_HANDLING.md)
4. Join our developer community
5. Set up monitoring and logging

## Need Help?

- Check our [FAQ](./RATE_LIMITING_AND_VALIDATION.md#faq)
- Review error messages in responses
- Enable debug mode for detailed logs
- Contact support with specific issues 