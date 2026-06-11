# User Logout

## POST /api/logout

Invalidates the current authentication token and ends the user session.

### Authentication
**Required** — Bearer token in `Authorization` header.

```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

### Request
No parameters required.

### Response

#### 200 OK — Logout Successful
```json
{
    "message": "Logged out successfully."
}
```

#### 401 Unauthorized — Missing or Invalid Token
```json
{
    "message": "Unauthenticated."
}
```

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `status` | `string` | `success` or `error` |
| `message` | `string` | Human-readable status message |

### Use Cases
- Token revocation
- Preventing further requests with invalidated tokens

### Notes
- After logout, the token is invalidated and cannot be used for further API requests
- Clients should discard the token locally after receiving a successful logout response
- Attempting to use an invalidated token will result in a 401 Unauthorized response
