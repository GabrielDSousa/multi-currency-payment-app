# Get Payment Request

## GET /api/payment-requests/{id}

Returns the full details of a single payment request.

### Authentication
**Required** — Bearer token in `Authorization` header.

```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

### Access Rules
| Role | Allowed |
|------|---------|
| `employee` | Only their own requests — returns `403` for any other record |
| `finance` | Any payment request |

### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | `integer` | Yes | Unique identifier of the payment request |

### Response

#### 200 OK — Success
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
        "status": "pending",
        "approved_by": null,
        "approved_at": null,
        "expired_at": null,
        "created_at": "2026-06-12T10:00:00+00:00",
        "updated_at": "2026-06-12T10:00:00+00:00"
    }
}
```

#### 401 Unauthorized — Missing or Invalid Token
```json
{
    "message": "Unauthenticated."
}
```

#### 403 Forbidden — Employee Accessing Another User's Request
```json
{
    "message": "This action is unauthorized.",
    "exception": "Symfony\\Component\\HttpKernel\\Exception\\AccessDeniedHttpException",
    "file": "/var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Exceptions/Handler.php",
    "line": 672,
    "trace": [ ... ]
}
```

#### 404 Not Found — Payment Request Does Not Exist
```json
{
    "message": "No query results for model [App\\Models\\Payment] -1",
    "exception": "Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException",
    "file": "/var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Exceptions/Handler.php",
    "line": 668,
    "trace": [ ... ]
}
```

### Fields

#### `data` — Payment Object

| Field | Type | Description |
|-------|------|-------------|
| `id` | `integer` | Unique identifier of the payment request |
| `user_id` | `integer` | ID of the employee who submitted the request |
| `description` | `string` | Human-readable reason for the payment |
| `amount_local` | `float` | Amount in the employee's local currency |
| `currency_code` | `string` | ISO 4217 code of the local currency (e.g. `BRL`) |
| `amount_eur` | `float` | Equivalent amount in EUR, calculated at creation time |
| `exchange_rate` | `float` | EUR → local rate captured at creation (immutable) |
| `rate_source` | `string` | Provider that supplied the exchange rate |
| `rate_timestamp` | `string` | ISO 8601 datetime when the exchange rate was fetched |
| `status` | `string` | Derived status: `pending`, `approved`, `rejected`, or `expired` |
| `approved_by` | `integer\|null` | ID of the finance user who actioned the request |
| `approved_at` | `string\|null` | ISO 8601 datetime of approval |
| `expired_at` | `string\|null` | ISO 8601 datetime of automatic expiry |
| `created_at` | `string` | ISO 8601 datetime of creation |
| `updated_at` | `string` | ISO 8601 datetime of last update |
| `exception` | `string` | Path of the Exception |
| `file` | `string` | Path where the exception occurred |
| `line` | `string` | Line number where the exception occurred |
| `trace` | `array` | List of method or function calls that led to the failure |

### Notes
- The `exchange_rate` field is captured once at request creation and never updated, even if the market rate changes.
- The `status` field is derived at response time — see the status derivation table below.

### Status Derivation Logic

| Condition | Derived `status` |
|-----------|-----------------|
| `expired_at` is not null | `expired` |
| `approved_at` is not null | `approved` |
| `pending` is `true` | `pending` |
| None of the above | `rejected` |

### Use Cases
- Employee checking the outcome of a submitted payment request
- Finance member reviewing full details before approving or rejecting
- Audit lookup of a specific transaction by ID
