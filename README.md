# EventHub

![CI](https://github.com/Victor017936/eventhub-api/actions/workflows/ci.yml/badge.svg)

EventHub is a full-stack event management and reservation application built with Laravel, Vue 3 and TypeScript.

Users can discover upcoming events, make reservations and manage their bookings. Administrators can manage categories and events, view participants and monitor platform statistics.

## Main features

### Public area

- View upcoming published events
- Search events by title or description
- Filter events by category, location and date
- View complete event details
- Responsive desktop and mobile interface
- Dynamic page titles and custom 404 page

### Authentication

- User registration
- JWT authentication
- Login and logout
- Persistent authenticated sessions
- Automatic handling of expired tokens
- Protected user and administrator routes
- Redirect to the requested page after login

### Reservations

- Reserve a place at an event
- Prevent duplicate reservations
- Enforce event capacity
- Enforce booking periods
- Reactivate cancelled reservations
- View personal reservations
- Cancel reservations
- Queue reservation confirmation emails

### Administration

- Dashboard statistics
- Create, edit and deactivate categories
- Create, edit, publish and cancel events
- Filter events by category, status and date
- View confirmed and cancelled participants
- Display event capacity and available places

## Technology stack

### Backend

- PHP 8.3
- Laravel 13
- MySQL 8
- Eloquent ORM
- JWT Authentication
- Laravel Queues
- Laravel Scheduler
- Laravel Pint
- Larastan / PHPStan
- Pest / PHPUnit

### Frontend

- Vue 3
- TypeScript
- Vue Router
- Pinia
- Axios
- Vite
- ESLint
- Vitest
- Vue Test Utils

### Continuous integration

GitHub Actions executes:

- PHP code-style verification
- PHP static analysis
- Backend tests
- ESLint
- TypeScript verification
- Frontend tests
- Production frontend build

## Requirements

- PHP 8.3 or newer
- Composer 2
- Node.js 22 or newer
- npm
- MySQL 8

## Installation

Clone the repository:

```bash
git clone https://github.com/Victor017936/eventhub-api.git
cd eventhub-api
```

Install backend dependencies:

```bash
composer install
```

Install frontend dependencies:

```bash
npm install
```

Create the environment file.

Linux or macOS:

```bash
cp .env.example .env
```

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Generate the Laravel application key and JWT secret:

```bash
php artisan key:generate
php artisan jwt:secret
```

Create the MySQL database:

```sql
CREATE DATABASE eventhub
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Verify the database settings in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=eventhub
DB_USERNAME=root
DB_PASSWORD=
```

Run migrations and create the demo data:

```bash
php artisan migrate --seed
```

## Running the application

### Start all development processes

```bash
composer run dev
```

This starts the Laravel server, queue listener, application logs and Vite.

Open:

```text
http://127.0.0.1:8000
```

### Start processes separately

Terminal 1:

```bash
php artisan serve
```

Terminal 2:

```bash
npm run dev
```

Terminal 3:

```bash
php artisan queue:work
```

Terminal 4:

```bash
php artisan schedule:work
```

## Demo accounts

### Administrator

```text
Email: admin@eventhub.test
Password: password
```

### Regular user

```text
Email: user@eventhub.test
Password: password
```

These accounts are intended only for local development and demonstrations.

## Main frontend pages

| Page | Path |
|---|---|
| Home | `/` |
| Public events | `/events` |
| Event details | `/events/{id}` |
| Personal reservations | `/my-reservations` |
| Admin dashboard | `/admin/dashboard` |
| Admin categories | `/admin/categories` |
| Admin events | `/admin/events` |
| Create event | `/admin/events/create` |
| Edit event | `/admin/events/{id}/edit` |
| Event participants | `/admin/events/{id}/reservations` |

## API overview

### Authentication

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/register` | Register a user |
| POST | `/api/login` | Authenticate and receive a JWT |
| POST | `/api/logout` | Log out |
| POST | `/api/refresh` | Refresh the JWT |
| GET | `/api/me` | Return the authenticated user |

### Categories

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/categories` | List active categories |
| GET | `/api/categories/{category}` | Show a category |
| POST | `/api/categories` | Create a category |
| PUT | `/api/categories/{category}` | Update a category |
| DELETE | `/api/categories/{category}` | Deactivate a category |

### Events

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/events` | List public upcoming events |
| GET | `/api/events/{event}` | Show a public event |
| POST | `/api/events` | Create an event |
| PUT | `/api/events/{event}` | Update an event |
| DELETE | `/api/events/{event}` | Cancel an event |
| GET | `/api/admin/events` | List all events as admin |
| GET | `/api/admin/events/{event}` | Show any event as admin |

### Reservations

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/events/{event}/reservations` | Create or reactivate a reservation |
| GET | `/api/my-reservations` | List personal reservations |
| GET | `/api/reservations/{reservation}` | Show an owned reservation |
| DELETE | `/api/reservations/{reservation}` | Cancel an owned reservation |
| GET | `/api/admin/events/{event}/reservations` | List event participants |

### Dashboard

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/admin/dashboard` | Return administrator statistics |

## Quality checks

PHP formatting:

```bash
vendor/bin/pint --test
```

PHP static analysis:

```bash
vendor/bin/phpstan analyse --memory-limit=1G
```

Backend tests:

```bash
php artisan test
```

ESLint:

```bash
npm run lint
```

TypeScript:

```bash
npm run type-check
```

Frontend tests:

```bash
npm run test
```

Production build:

```bash
npm run build
```

## Background processes

Reservation confirmation emails are processed through Laravel queues:

```bash
php artisan queue:work
```

Past published events are automatically marked as completed by the Laravel scheduler:

```bash
php artisan schedule:work
```

## Project structure

```text
app/
├── Enums/
├── Http/
│   ├── Controllers/Api/
│   └── Requests/
├── Jobs/
├── Models/
├── Policies/
└── Services/

resources/
├── css/
├── js/
│   ├── router/
│   ├── services/
│   ├── stores/
│   ├── tests/
│   ├── types/
│   └── views/
└── views/

routes/
├── api.php
└── web.php

tests/
├── Feature/
└── Unit/
```

## Security

- Protected endpoints require a valid JWT.
- Administrator operations are protected through Laravel policies.
- Requests are validated through dedicated form request classes.
- Passwords are securely hashed.
- Demo passwords must be changed before production deployment.

## License

This project is available under the MIT License.