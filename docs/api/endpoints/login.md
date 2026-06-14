# User Login

## POST /api/login

Authenticates a user and returns an authentication token.

### Authentication
**None** — Public endpoint.

### Request

#### Body Parameters
```json
{
    "email": "john@example.com",
    "password": "SecurePassword123!"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `email` | `string` | Yes | User's email address |
| `password` | `string` | Yes | User's password |

### Response

#### 200 OK — Login Successful
```json
{
    "message": "Login successful.",
    "user": {
        "id": 16,
        "name": "Finance User",
        "email": "finance_user@email.com",
        "country": "EUA",
        "currency_code": "USD",
        "department": "finance"
    },
    "token": {
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
        "token_type": "Bearer",
        "scopes": [
            "finance"
        ]
    }
}
```

#### 401 Unauthorized — Invalid credentials
```json
{
    "message": "Invalid credentials."
}
```

#### 422 Unprocessable Entity — Validation Error
```json
{
    "message": "The email field must be a valid email address. (and 1 more error)",
    "errors": {
        "email": [
            "The email field must be a valid email address."
        ],
        "password": [
            "The password field is required."
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
| `errors` | `object` | Validation or authentication errors |

### Use Cases
- User authentication/login flow
- Session establishment for API consumers
- Initial authentication before protected resource access
