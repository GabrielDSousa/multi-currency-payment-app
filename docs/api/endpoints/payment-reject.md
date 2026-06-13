# Reject Payment Request

## PATCH /api/payment/{id}/reject

Marks a pending payment request as rejected.

### Authentication
**Required** — Bearer token in `Authorization` header.

```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

### Access Rules
| Role | Allowed |
|------|---------|
| `finance` | ✅ Yes |
| `employee` | ❌ No — returns `403` |

### State Rules
Only a request in `status: pending` can be rejected. All other states return `400`.

| Current status | Result |
|----------------|--------|
| `pending` | ✅ Rejected successfully |
| `approved` | ❌ `400 Bad Request` |
| `rejected` | ❌ `400 Bad Request` |
| `expired` | ❌ `400 Bad Request` |

### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | `integer` | Yes | Unique identifier of the payment request to reject |

### Request
No body parameters required.

### Response

#### 200 OK — Rejected Successfully
```json
{
    "data": {
        "id": 42,
        "user_id": 7,
        "description": "Office supplies reimbursement",
        "amount_local": 1500.00,
        "currency_code": "BRL",
        "amount_eur": 276.75,
        "exchange_rate": 5.42,
        "rate_source": "exchangerate-api.com",
        "rate_timestamp": "2026-06-12T10:00:00+00:00",
        "status": "rejected",
        "approved_by": 3,
        "approved_at": null,
        "expired_at": null,
        "created_at": "2026-06-12T10:00:00+00:00",
        "updated_at": "2026-06-13T14:25:00+00:00"
    }
}
```

#### 400 Bad Request — Payment is Not Pending
```json
{
    "message": "Only pending requests can be rejected."
}
```

#### 401 Unauthorized — Missing or Invalid Token
```json
{
    "message": "Unauthenticated."
}
```

#### 403 Forbidden — Employee Attempting to Reject
```json
{
    "message": "This action is unauthorized.",
    "exception": "Symfony\\Component\\HttpKernel\\Exception\\AccessDeniedHttpException",
    "file": "/var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Exceptions/Handler.php",
    "line": 672,
    "trace": [ ... ]
}
```

#### 404 Not Found — Payment Does Not Exist
```json
{
    "message": "No query results for model [App\\Models\\Payment] 999999.",
    "exception": "Symfony\\Component\\HttpKernel\\Exception\\AccessDeniedHttpException",
    "file": "/var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Exceptions/Handler.php",
    "line": 672,
    "trace": [ ... ]
}
```

### Fields Updated on Rejection

| Field | Before | After |
|-------|--------|-------|
| `status` | `pending` | `rejected` |
| `pending` | `true` | `false` |
| `approved_by` | `null` | Finance user's ID |
| `approved_at` | `null` | Remains `null` |
| `exception` | `string` | Path of the Exception |
| `file` | `string` | Path where the exception occurred |
| `line` | `string` | Line number where the exception occurred |
| `trace` | `array` | List of method or function calls that led to the failure |

### Notes
- `approved_at` is **not** set on rejection — it is only populated on approval.
- `approved_by` is set to identify **who** actioned the request, even on rejection.
- The `exchange_rate`, `amount_eur`, and `rate_source` fields remain unchanged — they were captured at creation time and are immutable.
- After rejection, the request cannot be approved or rejected again — any further action returns `400`.
- `status: "rejected"` is derived at response time: `pending = false`, `approved_at = null`, `expired_at = null`.

### Use Cases
- Finance team declining payment requests that do not meet policy requirements
- Providing employees with a clear rejection signal so they can resubmit with corrections
