# Health Check

## GET /api/health

Returns the current health status of the API and its dependencies.

### Authentication
**None** — Public endpoint.

### Request
No parameters required.

### Response

#### 200 OK — All Systems Healthy
```json
{
    "status": "healthy",
    "timestamp": "2026-06-11T21:13:54+00:00",
    "environment": "local",
    "version": "1.0.0",
    "checks": {
        "database": {
            "status": "healthy",
            "message": "Database connection OK"
        },
        "cache": {
            "status": "healthy",
            "message": "Cache driver OK"
        },
        "queue": {
            "status": "healthy",
            "message": "Queue driver [database] OK"
        },
        "storage": {
            "status": "healthy",
            "message": "Storage read/write OK"
        },
        "app": {
            "status": "healthy",
            "message": "Laravel 12.62.0 running",
            "php_version": "8.5.7",
            "memory_usage": "4 MB"
        }
    }
}
```

#### 503 Service Unavailable — One or More Systems Unhealthy
```json
{
    "status": "unhealthy",
    "timestamp": "2026-06-10T15:24:00+00:00",
    "environment": "local",
    "version": "1.0.0",
    "checks": {
        "database": {
            "status": "unhealthy",
            "message": "SQLSTATE[HY000] [2002] Connection refused"
        },
        "cache": {
            "status": "healthy",
            "message": "Cache driver OK"
        }
    }
}
```

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `status` | `string` | `healthy` or `unhealthy` |
| `timestamp` | `string` | ISO 8601 datetime of the check |
| `environment` | `string` | Current app environment (`local`, `production`, etc.) |
| `version` | `string` | API version |
| `checks` | `object` | Individual status of each subsystem |
| `checks.*.status` | `string` | `healthy` or `unhealthy` |
| `checks.*.message` | `string` | Human-readable status message |
| `checks.app.php_version` | `string` | PHP version running the app |
| `checks.app.memory_usage` | `string` | Current memory consumption |

### Use Cases
- Docker health checks and container orchestration
- Load balancer health probes
- Uptime monitoring (Pingdom, UptimeRobot)
- CI/CD pipeline smoke tests
