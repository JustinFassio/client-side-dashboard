# API Endpoints

## Table of Contents
- [Overview](#overview)
- [Authentication](#authentication)
- [Profile Endpoints](#profile-endpoints)
- [Overview Endpoints](#overview-endpoints)
- [Common Response Formats](#common-response-formats)
- [Error Handling](#error-handling)

## Overview

All endpoints are prefixed with `/athlete-dashboard/v1/`. For example:
```
https://your-site.com/wp-json/athlete-dashboard/v1/profile/123
```

## Authentication

All endpoints require authentication via WordPress nonces. Include the nonce in your request headers:
```http
X-WP-Nonce: your_nonce_here
```

## Profile Endpoints

### Get Profile

```http
GET /profile/{id}
```

Retrieves a user's profile data.

#### Parameters
| Name | Type | In | Description |
|------|------|------|------------|
| id | integer | path | The user ID |

#### Response
```json
{
    "id": 123,
    "username": "johndoe",
    "email": "john@example.com",
    "firstName": "John",
    "lastName": "Doe",
    "age": 25,
    "height": 180,
    "weight": 75,
    "medicalNotes": "No current conditions",
    "injuries": [
        {
            "name": "Sprained Ankle",
            "description": "Left ankle sprain",
            "date": "2024-01-15",
            "status": "active"
        }
    ]
}
```

### Update Profile

```http
POST /profile/{id}
```

Updates a user's profile data.

#### Parameters
| Name | Type | In | Description |
|------|------|------|------------|
| id | integer | path | The user ID |
| firstName | string | body | First name |
| lastName | string | body | Last name |
| email | string | body | Email address |
| age | integer | body | Age (13-120) |
| height | float | body | Height in cm |
| weight | float | body | Weight in kg |
| injuries | array | body | Array of injury objects |

#### Request Body Example
```json
{
    "firstName": "John",
    "lastName": "Doe",
    "email": "john@example.com",
    "age": 25,
    "height": 180,
    "weight": 75,
    "injuries": [
        {
            "name": "Sprained Ankle",
            "description": "Left ankle sprain",
            "date": "2024-01-15",
            "status": "active"
        }
    ]
}
```

### Bulk Update Profiles (Admin Only)

```http
POST /profile/bulk
```

Updates multiple user profiles in a single request.

#### Parameters
| Name | Type | In | Description |
|------|------|------|------------|
| profiles | array | body | Array of profile objects |

#### Request Body Example
```json
{
    "profiles": [
        {
            "id": 123,
            "firstName": "John",
            "lastName": "Doe"
        },
        {
            "id": 124,
            "firstName": "Jane",
            "lastName": "Smith"
        }
    ]
}
```

## Overview Endpoints

### Get Overview Data

```http
GET /overview/{user_id}
```

Retrieves overview data for a user including stats, recent activity, and goals.

#### Parameters
| Name | Type | In | Description |
|------|------|------|------------|
| user_id | integer | path | The user ID |

#### Response
```json
{
    "stats": {
        "workouts_completed": 5,
        "active_programs": 2,
        "nutrition_score": 80
    },
    "recent_activity": [
        {
            "id": 1,
            "type": "workout",
            "title": "Completed Workout",
            "date": "2024-01-15"
        }
    ],
    "goals": [
        {
            "id": 1,
            "title": "Weight Loss Goal",
            "progress": 60,
            "target_date": "2024-03-01"
        }
    ]
}
```

### Update Goal Progress

```http
POST /overview/goals/{goal_id}
```

Updates the progress of a specific goal.

#### Parameters
| Name | Type | In | Description |
|------|------|------|------------|
| goal_id | integer | path | The goal ID |
| progress | integer | body | Progress percentage (0-100) |

#### Request Body Example
```json
{
    "progress": 75
}
```

### Dismiss Activity

```http
DELETE /overview/activity/{activity_id}
```

Dismisses a specific activity from the overview.

#### Parameters
| Name | Type | In | Description |
|------|------|------|------------|
| activity_id | integer | path | The activity ID |

## Common Response Formats

### Success Response
```json
{
    "data": {
        // Response data
    },
    "status": 200
}
```

### Error Response
```json
{
    "code": "error_code",
    "message": "Human-readable error message",
    "data": {
        "status": 400,
        // Additional error details
    }
}
```

### Rate Limit Headers
All responses include rate limit information:
```http
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 3600
```

## Error Handling

Common error codes:

| Code | Description |
|------|-------------|
| invalid_credentials | Invalid authentication |
| rate_limit_exceeded | Too many requests |
| validation_failed | Invalid request data |
| permission_denied | Insufficient permissions |
| not_found | Resource not found |

For detailed error handling information, see [Error Handling Guide](./ERROR_HANDLING.md). 