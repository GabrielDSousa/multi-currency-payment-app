# List Payment Requests

## GET /api/payment-requests

Returns a paginated list of payment requests (15 items per page).

### Authentication
**Required** — Bearer token in `Authorization` header.

```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

### Access Rules
| Role | Visible records |
|------|-----------------|
| `employee` | Only their own requests. The `employee_id` filter is silently ignored. |
| `finance` | All requests. Can additionally filter by `employee_id`. |

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `status` | `string` | No | Filter by status: `pending`, `approved`, `rejected`, or `expired` |
| `currency` | `string` | No | ISO 4217 currency code (3 letters, case-insensitive) |
| `date_from` | `date` | No | Include requests created on or after this date (`YYYY-MM-DD`) |
| `date_to` | `date` | No | Include requests created on or before this date (`YYYY-MM-DD`). Must be ≥ `date_from` |
| `employee_id` | `integer` | No | Filter by employee ID — **finance only** |
| `page` | `integer` | No | Page number (1-based, defaults to `1`) |

### Response

#### 200 OK — Success
```json
{
    "data": [
        {
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
    ],
    "links": {
        "first": "http://localhost/api/payment-requests?page=1",
        "last": "http://localhost/api/payment-requests?page=2",
        "prev": null,
        "next": "http://localhost/api/payment-requests?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 2,
        "per_page": 15,
        "to": 15,
        "total": 20
    }
}
```

#### 401 Unauthorized — Missing or Invalid Token
```json
{
    "message": "Unauthenticated."
}
```

#### 422 Unprocessable Entity — Invalid Query Parameters
```json
{
    "message": "The status field must be one of pending, approved, rejected, expired.",
    "errors": {
        "status": [
            "The status field must be one of pending, approved, rejected, expired."
        ]
    }
}
```

### Fields

#### `data[]` — Payment Object

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

#### `links` — Pagination Links

| Field | Type | Description |
|-------|------|-------------|
| `first` | `string` | URL of the first page |
| `last` | `string` | URL of the last page |
| `prev` | `string\|null` | URL of the previous page, `null` on the first page |
| `next` | `string\|null` | URL of the next page, `null` on the last page |

#### `meta` — Pagination Metadata

| Field | Type | Description |
|-------|------|-------------|
| `current_page` | `integer` | Current page number |
| `from` | `integer` | Index of the first item on this page (1-based) |
| `last_page` | `integer` | Total number of pages |
| `links` | `array` | List of pagination links objects containing url, label, page and active status |
| `path` | `string` | URL of the request |
| `last_page` | `integer` | Total number of pages |
| `per_page` | `integer` | Items per page — always `15` |
| `to` | `integer` | Index of the last item on this page (1-based) |
| `total` | `integer` | Total records matching the current filters |

### Status Derivation Logic

The `status` field is derived at response time from the underlying database columns:

| Condition | Derived `status` |
|-----------|-----------------|
| `expired_at` is not null | `expired` |
| `approved_at` is not null | `approved` |
| `pending` is `true` | `pending` |
| None of the above | `rejected` |

### Use Cases
- Dashboard listing all payment requests for a finance member
- Employee checking the status of their own submissions
- Filtering by date range for monthly finance reporting
- Audit trail filtered by currency or employee
