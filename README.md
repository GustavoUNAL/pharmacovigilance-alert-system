# Pharmacovigilance Alert System

Full-stack application built with Laravel and Vue.js to identify and notify customers who purchased medications associated with a specific lot number.

---

## Overview

This project implements a pharmacovigilance-oriented module suitable for pharmacy operations. It allows authenticated users to search for medications by lot number (and optional purchase date range), review affected orders and customer contact data, and send structured alert e-mails. Completed alert actions are persisted for audit purposes.

---

## Tech Stack

- **Backend:** Laravel (PHP)
- **Frontend:** Vue.js 3 with Vite
- **Authentication:** Laravel Sanctum (cookie-based session for same-origin SPA)
- **Database:** MySQL (or any database supported by Laravel; configure via `.env`)
- **API:** REST-style JSON endpoints under `/api`
- **E-mail:** Laravel Mail (use `log` driver locally or SMTP in production)

---

## Features

### Authentication

- Session-based login restricted to registered users
- CSRF protection for stateful API requests from the web client
- Endpoints for current user and logout

### Medication search

- Search by **lot number** (required)
- Optional **start date** and **end date** filters on order purchase date
- Client-side validation and clear feedback when no orders match the criteria

### Orders retrieval

- List orders that include at least one line item whose medication matches the given lot number
- Response includes order identifier, purchase date, customer name, e-mail, phone, and medication summaries for display and alert context

### Orders table (UI)

- Tabular presentation of search results
- Actions: view order details, view buyer (customer) details, open alert workflow

### Alert system

- Sends an e-mail to the customer associated with the selected order
- Message content includes a warning, lot reference, and recommended follow-up language
- Records each sent alert with customer ID, order ID, and timestamp

---

## API Endpoints

All routes below use the `/api` prefix. Except where noted, requests require an authenticated session (same-origin cookies after login).

| Method | Endpoint | Description |
| --- | --- | --- |
| GET | `/sanctum/csrf-cookie` | Issues CSRF cookie for SPA (call before login or mutating requests from the browser) |
| POST | `/api/login` | Authenticate with `username` and `password`; establishes session |
| GET | `/api/user` | Return the authenticated user’s profile |
| POST | `/api/logout` | End session |
| GET | `/api/orders` | Query parameters: `lot` (required), `start_date`, `end_date` (`Y-m-d`). Returns matching orders with nested customer and medication data |
| POST | `/api/alerts/send` | JSON body: `order_id`. Queues/sends alert e-mail and creates an `alerts` row |

Order and customer detail views in the frontend use data returned by `GET /api/orders`; there are no separate `GET /api/orders/{id}` or `GET /api/customers/{id}` resources.

---

## Database schema

Main entities:

- Users
- Customers
- Medications
- Orders
- Order items
- Alerts

Relationships (high level):

- Customer has many orders
- Order belongs to customer and has many order items
- Order item belongs to order and medication
- Alert belongs to customer and order

---

## Sample data

Seeders provide representative customers, medications (including lot `951357`), orders, and order lines. An administrative application user is also created for immediate sign-in (see Test credentials).

---

## Installation

```bash
git clone https://github.com/your-username/pharmacovigilance-alert-system.git
cd pharmacovigilance-alert-system
```

### 1. Install dependencies

```bash
composer install
npm install
```

### 2. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Configure the database in `.env`, for example:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pharma
DB_USERNAME=root
DB_PASSWORD=
```

Set `MAIL_MAILER` and related variables according to your environment (e.g. `log` for local testing).

### 3. Run migrations and seeders

```bash
php artisan migrate
php artisan db:seed
```

### 4. Run the application

In separate terminals:

```bash
php artisan serve
```

```bash
npm run dev
```

For production asset builds:

```bash
npm run build
```

Open the application at:

```text
http://127.0.0.1:8000
```

Ensure `SANCTUM_STATEFUL_DOMAINS` in `.env` includes the host and port you use (see `config/sanctum.php`) so session authentication from the Vue SPA works correctly.

---

## Test credentials

```text
username: admin
password: password
```

---

## License

This project is provided as application source code. The underlying Laravel framework is open source under the [MIT license](https://opensource.org/licenses/MIT).
