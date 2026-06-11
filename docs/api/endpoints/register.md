# User Registration

## POST /api/register

Creates a new user account and returns an authentication token.

### Authentication
**None** — Public endpoint.

### Request

#### Body Parameters
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "SecurePassword123!",
    "password_confirmation": "SecurePassword123!",
    "country": "BR",
    "currency_code": "BRL"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | `string` | Yes | User's full name (1-255 characters) |
| `email` | `string` | Yes | Valid email address (must be unique) |
| `password` | `string` | Yes | Password (min. 8 characters, must include uppercase, lowercase, number, special character) |
| `country` | `string` | Yes | Two letters that represent the user's country
| `currency_code` | `string` | Yes | Three letters that represent the user's currency

### Response

#### 201 Created — Registration Successful
```json
{
    "message": "User registered successfully.",
    "user": {
        "id": 18,
        "name": "John Doe",
        "email": "john@example.com",
        "country": "BR",
        "currency_code": "BRL",
        "department": "employee"
    },
    "token": {
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
        "token_type": "Bearer",
        "scopes": [
            "employee"
        ]
    }
}
```

#### 422 Unprocessable Entity — Validation Error
```json
{
    "message": "The name field is required. (and 4 more errors)",
    "errors": {
        "name": [
            "The name field is required."
        ],
        "email": [
            "The email field is required."
        ],
        "password": [
            "The password field is required."
        ],
        "country": [
            "The country field is required."
        ],
        "currency_code": [
            "The currency code field is required."
        ]
    }
}
```

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `message` | `string` | Human-readable status message |
| `user.id` | `string` | UUID of the authenticated user |
| `user.name` | `string` | User's full name |
| `user.email` | `string` | User's email address |
| `user.country`| `string` | Two letters that represent the user's country |
| `user.currency code`| `string` | Three letters that represent the user's currency |
| `token.access_token` | `string` | Bearer access token for authentication |
| `token.token_type` | `string` | Token type (always `Bearer`) |
| `token.scopes` | `array` | Token scopes (always `employee` or `finance`) |
| `errors` | `object` | Validation errors |

### Use Cases
- User account creation
- Self-service registration flow
- Initial setup for new API consumers
