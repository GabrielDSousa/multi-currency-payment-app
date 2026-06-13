# 💱 Multi-Currency Payment API

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP Version">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel Version">
  <img src="https://img.shields.io/badge/Passport-OAuth2-4A90D9?style=for-the-badge&logo=auth0&logoColor=white" alt="Laravel Passport">
  <img src="https://img.shields.io/badge/Docker-Sail-2496ED?style=for-the-badge&logo=docker&logoColor=white" alt="Docker">
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="License">
</p>

<p align="center">
  A RESTful API for creating, listing, and approving/rejecting payment requests across multiple currencies. Built with Laravel 12, OAuth2 authentication via Laravel Passport, and full test coverage using PHPUnit.
</p>

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Prerequisites](#prerequisites)
- [Getting Started](#getting-started)
- [Configuration](#configuration)
- [Running Tests](#running-tests)
- [API Reference](#api-reference)
- [Test Accounts](#test-accounts)
- [Troubleshooting](#troubleshooting)
- [License](#license)
- [Contact](#contact)

---

## Features

- 🔐 **OAuth2 Authentication** — Secure token-based auth via Laravel Passport (Personal Access Client)
- 💵 **Multi-Currency Support** — Payment requests in different currencies with real-time exchange rates
- 👥 **Role-Based Access Control** — `employee` and `finance` scopes enforced at the route level
- ✅ **Approval Workflow** — Finance users can approve or reject pending payment requests
- 🧪 **TDD** — Full test suite built with PHPUnit and Laravel Factories/Fakers
- 🐳 **Dockerized** — Ready-to-run with Laravel Sail

---

## Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.x |
| Framework | Laravel 12 |
| Authentication | Laravel Passport (OAuth2) |
| Database | MySQL (via Docker) |
| Infrastructure | Laravel Sail / Docker Compose |
| Testing | PHPUnit |
| Code Style | Laravel Pint |
| Exchange Rates | [ExchangeRate-API](https://www.exchangerate-api.com/) |

---

## Prerequisites

- [Composer](https://getcomposer.org/)
- [Docker](https://www.docker.com/) and Docker Compose

---

## Getting Started

### 1. Clone the repository

```bash
git clone git@github.com:GabrielDSousa/multi-currency-payment-app.git
cd multi-currency-payment-app
```

### 2. Copy the environment file

```bash
cp .env.example .env
```

### 3. Set the Exchange Rate API key

Open `.env` and fill in your key (free tier available at [exchangerate-api.com](https://www.exchangerate-api.com/)):

```env
EXCHANGERATE_API_KEY=your_key_here
```

### 4. Install dependencies

```bash
composer install
```

### 5. Start the containers

```bash
./vendor/bin/sail up -d --build
```

### 6. Prepare the application

```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan passport:keys
```

### 7. Run migrations and seed the database

```bash
./vendor/bin/sail artisan migrate:fresh --seed --force
```

> **Note:** The `DatabaseSeeder` runs `PassportPersonalClientSeeder`, `UserSeeder`, and `PaymentSeeder` in order. Always run migrations before seeding to avoid `oauth_clients table not found` errors.

---

## Configuration

### Passport Client

The `PassportPersonalClientSeeder` automatically runs `passport:client --personal`. If you ever need to recreate it manually:

```bash
./vendor/bin/sail artisan passport:client --personal --no-interaction
```

### Authentication Flow

1. **Login** — `POST /api/login` with `{ email, password }` returns a Bearer token.
2. **Authenticated requests** — Pass the token in the `Authorization` header:

```
Authorization: Bearer <your_token_here>
```

---

## Running Tests

```bash
./vendor/bin/sail test
```

---

## API Reference

Full specification: [`openapi.yaml`](openapi.yaml)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/api/health` | Public | System health check |
| `POST` | `/api/register` | Public | Register a new user |
| `POST` | `/api/login` | Public | Authenticate and receive token |
| `POST` | `/api/logout` | `auth:api` | Revoke current token |
| `GET` | `/api/payment` | `auth:api` | List all payments |
| `GET` | `/api/payment/{payment}` | `auth:api` | Get a single payment |
| `PATCH` | `/api/payment/{payment}/approve` | `auth:api`, `role:finance` | Approve a payment |
| `PATCH` | `/api/payment/{payment}/reject` | `auth:api`, `role:finance` | Reject a payment |

Detailed docs per endpoint:
- [Auth endpoints](docs/api/endpoints/auth.md)
- [Payment list](docs/api/endpoints/payment-list.md)
- [Approve payment](docs/api/endpoints/payment-approve.md)
- [Reject payment](docs/api/endpoints/payment-reject.md)

---

## Test Accounts

The seeder creates the following accounts for local development:

| Role | Email | Password | Department |
|------|-------|----------|------------|
| Finance | `finance_user@email.com` | `SenhaSegura123!` | finance |
| Employee | `employee_user@email.com` | `SenhaSegura123!` | employee |

---

## Troubleshooting

**`oauth_clients table not found` during seeding**
Run migrations before seeding:
```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed
```

**Issues with Docker Compose**
Double-check file paths and permissions for any local Dockerfiles. Make sure all containers are healthy:
```bash
./vendor/bin/sail ps
```

---

## License

Distributed under the [MIT License](LICENSE).

---

## Contact

**Gabriel D. Sousa** — gabrielramos.email@gmail.com

Project repository: [github.com/GabrielDSousa/multi-currency-payment-app](https://github.com/GabrielDSousa/multi-currency-payment-app)
