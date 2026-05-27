# Binar Fullstack PHP Code Test

A clean Laravel 11 REST API demonstrating service-layer architecture, policy-based authorization, and structured JSON responses.

## Stack

- PHP 8.2-fpm
- Laravel 11
- PostgreSQL 16
- Nginx
- Mailpit (local SMTP UI at http://localhost:8025)
- Laravel Sanctum (API tokens)
- L5-Swagger (Swagger UI at http://localhost:8080/api/documentation)

## Quick Start

### 1. Start the containers

```bash
docker compose up -d
```

### 2. Install PHP dependencies

```bash
docker compose exec app composer install
```

### 3. Copy the environment file and generate the app key

```bash
cp src/.env.example src/.env
docker compose exec app php artisan key:generate
```

> Skip `key:generate` if `APP_KEY` is already set in `src/.env`.

### 4. Fix storage permissions

```bash
docker compose exec app chmod -R 775 storage bootstrap/cache
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### 5. Run migrations and seed demo data

```bash
docker compose exec app php artisan migrate --seed
```

The seeder will print three API tokens to the console — one per role.

### 6. Open Swagger UI

http://localhost:8080/api/documentation

Use the printed tokens to authorize and test endpoints directly.

## Demo Credentials

| Role          | Email               | Password    |
|---------------|---------------------|-------------|
| administrator | admin@binar.co      | password123 |
| manager       | manager@binar.co    | password123 |
| user          | user@binar.co       | password123 |

## API Tokens

Tokens are printed to the console during `db:seed`. They do not expire and persist as long as the Docker volume exists.

If you need fresh tokens (e.g. after re-seeding or losing the console output), run:

```bash
docker compose exec app php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

App\Models\User::all()->each(function (\$user) {
    \$user->tokens()->delete();
    \$token = \$user->createToken('seed-token')->plainTextToken;
    echo \$user->email . ' (' . \$user->role->value . '): ' . \$token . PHP_EOL;
});
"
```

> Use the full token including the `id|` prefix in the `Authorization: Bearer` header.

## API Endpoints

| Method | Path        | Auth required | Description       |
|--------|-------------|---------------|-------------------|
| POST   | /api/users  | No            | Create a new user |
| GET    | /api/users  | Bearer token  | List active users |

## Running Tests

```bash
docker compose exec app ./vendor/bin/phpunit
```

## Mails

All outbound emails are captured by Mailpit. View them at http://localhost:8025.

## Architecture Notes

- **Thin controllers** — controllers only receive, delegate to a service, and return a response.
- **Service layer** — `UserService` owns business logic (create user, list with can_edit).
- **Form Requests** — validation at the boundary; services receive already-clean data.
- **API Resources** — `UserResource` shapes all user responses; no raw model leakage.
- **Policy** — `UserPolicy::edit` encapsulates the can_edit rule per role in one testable place.
- **ApiResponse** — single helper class defines all response shapes; no ad-hoc `response()->json()` in controllers.
- **Mails** — `WelcomeUserMail` and `NewUserAdminNotificationMail` are `Mailable` classes with Blade views. In production they would implement `ShouldQueue`.
