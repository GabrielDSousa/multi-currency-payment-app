# Create Payment

## POST /api/payment

Submits a new payment request in the employee's local currency.

The exchange rate (EUR → local currency) is fetched automatically from
**exchangerate-api.com** at the time of submission and stored immutably alongside the record. The EUR equivalent (`amount_eur`) is computed server-side and returned in the response.

### Authentication
**Required** — Bearer token in `Authorization` header.

```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

### Access Rules
| Role | Allowed |
|------|---------|
| `employee` | ✅ Yes |
| `finance` | ✅ Yes |

### Request Body

```json
{
    "amount_local": 1500.00,
    "currency_code": "BRL",
    "description": "Office supplies reimbursement"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `amount_local` | `float` | Yes | Amount in the employee's local currency. Must be greater than `0` |
| `currency_code` | `string` | Yes | ISO 4217 currency code — exactly 3 alpha characters (e.g. `BRL`, `USD`). Case-insensitive, stored as uppercase |
| `description` | `string` | No | Human-readable reason for the payment. Max 1000 characters |

> **Note:** `user_id` is inferred from the Bearer token — it cannot be set via the request body.

### Response

#### 201 Created — Payment Submitted Successfully
```json
{
    "data": {
        "id": 42,
        "user_id": 7,
        "description": "Office supplies reimbursement",
        "amount_local": 1500.00,
        "currency_code": "BRL",
        "amount_eur": 276.7528,
        "exchange_rate": 5.42,
        "rate_source": "exchangerate-api.com",
        "rate_timestamp": "2026-06-13T10:00:00+00:00",
        "status": "pending",
        "approved_by": null,
        "approved_at": null,
        "expired_at": null,
        "created_at": "2026-06-13T10:00:00+00:00",
        "updated_at": "2026-06-13T10:00:00+00:00"
    }
}
```

#### 401 Unauthorized — Missing or Invalid Token
```json
{
    "message": "Unauthenticated."
}
```

#### 422 Unprocessable Entity — Validation Error
```json
{
    "message": "The amount local field is required. (and 1 more error)",
    "errors": {
        "amount_local": [
            "The amount local field is required."
        ],
        "currency_code": [
            "The currency code field is required."
        ]
    }
}
```

#### 503 Service Unavailable — Exchange Rate API Down
```json
{
    "message": "Exchange rate service is temporarily unavailable. Please try again later.",
    "exception": "Symfony\\Component\\HttpKernel\\Exception\\HttpException",
    "file": "/var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Application.php",
    "line": 1440,
    "trace": [ ... ]
}
```

### Response Fields (`data`)

| Field | Type | Description |
|-------|------|-------------|
| `id` | `integer` | Unique identifier of the payment request |
| `user_id` | `integer` | ID of the employee who submitted the request |
| `description` | `string\|null` | Human-readable reason for the payment |
| `amount_local` | `float` | Amount in the employee's local currency |
| `currency_code` | `string` | ISO 4217 code — always uppercase (e.g. `BRL`) |
| `amount_eur` | `float` | EUR equivalent, computed as `amount_local / exchange_rate` (4 decimal places) |
| `exchange_rate` | `float` | EUR → local rate captured at creation — **immutable** (6 decimal places) |
| `rate_source` | `string` | Provider that supplied the rate (`exchangerate-api.com`) |
| `rate_timestamp` | `string` | ISO 8601 datetime when the rate was fetched |
| `status` | `string` | Always `pending` on creation |
| `approved_by` | `integer\|null` | Always `null` on creation |
| `approved_at` | `string\|null` | Always `null` on creation |
| `expired_at` | `string\|null` | Always `null` on creation |
| `created_at` | `string` | ISO 8601 creation datetime |
| `updated_at` | `string` | ISO 8601 last-update datetime |

### Validation Rules

| Field | Rules |
|-------|-------|
| `amount_local` | Required, numeric, greater than `0`, max `99999999999` |
| `currency_code` | Required, exactly 3 alpha characters |
| `description` | Optional, string, max `1000` characters |

### Exchange Rate Behaviour

- Rate is fetched from `exchangerate-api.com` at submission time.
- The rate is **cached for 1 hour** — repeated submissions in the same hour reuse the cached value (no extra API call).
- The stored `exchange_rate` is **immutable** — it never changes even if the market rate fluctuates after submission.
- If the exchange rate API is unreachable, the endpoint returns `503` and **no payment record is created**.

### Status Lifecycle

```
[created] → pending → approved
                    → rejected
                    → expired (after 48h with no action)
```

### Use Cases
- Employee submitting a reimbursement or vendor payment request
- Finance member submitting a payment on behalf of a team
