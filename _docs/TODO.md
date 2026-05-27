# PHP Code Test — TODO

## Goal
Build a clean, well-structured Laravel REST API. The reviewer cares more about
code clarity, naming, and maintainability than correctness of the final output.

---

## Tasks

### Infrastructure (Docker)
- [x] `docker-compose.yml` — services: `app` (PHP-FPM), `nginx`, `postgres`, `mailpit` (local SMTP UI)
- [x] `docker/php/Dockerfile` — PHP 8.2-fpm + composer
- [x] `docker/nginx/default.conf` — point root to `/var/www/html/public`
- [x] `.env.example` — pre-filled with docker service names (DB_HOST=postgres, MAIL_HOST=mailpit, etc.)
- [x] `README.md` — `docker compose up -d` → `docker compose exec app php artisan migrate --seed` → done

### API Documentation (Swagger)
- [x] Install `darkaonline/l5-swagger`
- [x] Annotate `UserController` with `@OA` docblocks (POST /api/users, GET /api/users)
- [x] Swagger UI accessible at `/api/documentation` — reviewer can try endpoints directly in browser
- [x] No Postman collection needed (Swagger is self-contained and always up-to-date)

### Response Helpers
- [x] `ApiResponse` class — `success($data, $status)`, `failed($message, $errors)`, `error($message)`

### Setup
- [x] Create new Laravel project
- [x] Configure `.env` (DB, mail, admin email)
- [x] Create migrations for `users` and `orders` tables

### Database
- [x] Migration: `users` — id, email, password, name, role (string, default: 'user'), active (bool, default: true), created_at
- [x] PHP Enum: `UserRole` (backed string enum: administrator, manager, user) — cast on the User model
- [x] Migration: `orders` — id, user_id (FK), created_at
- [x] Model: `User` with `hasMany(Order)`
- [x] Model: `Order` with `belongsTo(User)`

### POST /api/users — Create User
- [ ] Form Request: `CreateUserRequest` — update validation rules per spec (name regex, password min:8 max:20 complexity, email max:255)
- [x] Service: `UserService@createUser` — hash password, insert record
- [x] Mailable: `WelcomeUserMail` — confirmation email to new user
- [x] Mailable: `NewUserAdminNotificationMail` — notify admin
- [x] Controller: `UserController@store` — call service, dispatch mails, return `UserResource`
- [x] API Resource: `UserResource` — id, email, name, created_at (no password)

### GET /api/users — List Users
- [x] Form Request: `ListUsersRequest` — validate search, page, sortBy
- [x] Service: `UserService@listUsers` — filter active, search, sort, paginate, eager-load orders count
- [x] Policy / helper: `UserEditPolicy` — can_edit logic (admin: any, manager: role=user only, user: self only)
- [x] API Resource: `UserResource` (extended or separate) — add role, orders_count, can_edit
- [x] Controller: `UserController@index` — call service, return paginated resource

### Auth (minimal, for can_edit context)
- [x] Use Laravel Sanctum or a simple stub so `auth()->user()` returns the acting user in tests
- [x] Route middleware: `auth:sanctum` on GET /api/users

### Tests (bonus, shows quality)
- [x] Feature test: POST /api/users — happy path, validation errors
- [x] Feature test: GET /api/users — search, sort, can_edit per role

### Performance
- [x] `withCount('orders')` — single subquery, no N+1
- [x] `$query->when(...)` — no unnecessary query branches
- [x] Pagination — 15 per page, never loads all users
- [ ] DB indexes — add index on `name` and `created_at` in users migration (`email` already indexed via unique)
- [ ] Comment on `LIKE '%keyword%'` limitation in `UserService` — leading wildcard can't use B-tree index; acceptable at this scale, full-text search at scale

---

## Code Structure

```
binar-fullstack-php/
├── docker/
│   ├── php/
│   │   └── Dockerfile
│   └── nginx/
│       └── default.conf
├── docker-compose.yml
│
└── src/                          # Laravel project root
    ├── app/
    │   ├── Enums/
    │   │   └── UserRole.php          # backed string enum: admin, manager, user
    │   │
    │   ├── Http/
    │   │   ├── Controllers/
    │   │   │   └── UserController.php    # thin: validate → service → resource
    │   │   ├── Requests/
    │   │   │   ├── CreateUserRequest.php
    │   │   │   └── ListUsersRequest.php
    │   │   └── Resources/
    │   │       └── UserResource.php      # shapes both POST and GET responses
    │   │
    │   ├── Mail/
    │   │   ├── WelcomeUserMail.php
    │   │   └── NewUserAdminNotificationMail.php
    │   │
    │   ├── Models/
    │   │   ├── User.php
    │   │   └── Order.php
    │   │
    │   ├── Http/Responses/
    │   │   └── ApiResponse.php           # success(), failed(), error() helpers
    │   │
    │   ├── Policies/
    │   │   └── UserPolicy.php            # can_edit logic per role
    │   │
    │   └── Services/
    │       └── UserService.php           # createUser(), listUsers()
    │
    ├── database/
    │   ├── migrations/
    │   │   ├── ..._create_users_table.php
    │   │   └── ..._create_orders_table.php
    │   └── seeders/
    │       └── DatabaseSeeder.php        # seed demo users for reviewer
    │
    ├── resources/views/emails/
    │   ├── welcome-user.blade.php
    │   └── new-user-admin-notification.blade.php
    │
    ├── routes/
    │   └── api.php                       # POST /api/users, GET /api/users
    │
    └── tests/Feature/
        └── UserApiTest.php
```

---

## Validation Rules

### POST /api/users

| Field | Rules |
|---|---|
| `name` | required, string, min:3, max:50, letters/spaces/accented chars only — `regex:/^[\pL\s]+$/u` |
| `email` | required, valid email format, unique in `users`, max:255 |
| `password` | required, min:8, max:20, must contain uppercase, lowercase, number, and special character |

### GET /api/users

| Field | Rules |
|---|---|
| `search` | optional, string, max:100 |
| `sortBy` | optional, one of: `name`, `email`, `created_at` |
| `page` | optional, integer, min:1 |

---

## Response Format

### Success (2xx)
```json
{
    "success": true,
    "data": { ... }
}
```

### Failed — validation / business rule (4xx)
```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email has already been taken."]
    }
}
```

### Error — unexpected server error (5xx)
```json
{
    "success": false,
    "message": "Something went wrong."
}
```

Implemented via a single `ApiResponse` helper class — controller calls `ApiResponse::success($data)` or `ApiResponse::error($message)`, shape is defined in one place.

### Paginated Success (2xx)
```json
{
    "success": true,
    "data": {
        "page": 1,
        "per_page": 15,
        "total": 42,
        "users": [ ... ]
    }
}
```

---

## Clean Code Rules

**Naming**
- Classes: `PascalCase`, methods/variables: `camelCase`, DB columns/env vars: `snake_case`
- Names should say what it is/does — no abbreviations (`$userService` not `$svc`)
- Booleans prefixed: `$isActive`, `$canEdit`

**Methods**
- One method, one job — if you need "and" to describe it, split it
- Keep methods short — if it doesn't fit on screen, it's doing too much

**Comments**
- No comments that explain *what* — the code should do that
- Only comments for *why* — non-obvious constraints, workarounds, production notes

**Controllers**
- No business logic — only: receive request → call service → return response

**No magic strings**
- Role values come from `UserRole` enum, never a raw `'admin'` string

**Fail fast**
- Validate at the boundary (`FormRequest`), trust clean data inside the service

---

## Laravel Ecosystem & Best Practices

**Eloquent**
- Use `withCount('orders')` for `orders_count` — no raw JOINs
- Use `$query->when($param, fn(...))` for optional filters — no `if` blocks in query builders
- Define `$fillable` on models — no mass assignment vulnerabilities
- Use model `$casts` for `role` (→ `UserRole` enum) and `active` (→ boolean)

**Validation**
- Always use `FormRequest` — never validate inline in the controller
- Return 422 with structured errors automatically via Laravel's default behavior

**Authorization**
- Use Laravel `Policy` for `can_edit` — auto-discovered by model name, easy to test in isolation
- Register via `Gate` or `$user->can()` — no manual role checks scattered in the code

**Response Shaping**
- Use `JsonResource` / `ResourceCollection` — never return raw model or manual array from controller
- Use `$this->when($condition, $value)` in resources for conditional fields

**Mail**
- Use `Mailable` classes with Blade views — never use `Mail::raw()` or closures
- Note in code: should be queued (`implements ShouldQueue`) in production

**Auth**
- Use Laravel Sanctum for API token auth
- Seed one token per role so reviewer can test `can_edit` behavior without a login endpoint

**Configuration**
- All environment-specific values via `.env` + `config()` — never hardcode
- `ADMIN_EMAIL` in `.env`, read via `config('app.admin_email')`

**Error Handling**
- Use `bootstrap/app.php` exception handler to format all error/failed responses consistently — one place, not scattered try/catch in controllers

**General**
- Use `Hash::make()` for passwords — never plain text or custom hashing
- Use PHP backed enums (`UserRole`) — never raw strings like `'admin'` in logic
- Fat service, thin controller — controller only orchestrates, never decides

---

## Notes & Gotchas

- **Swagger view bug** — `resources/views/vendor/l5-swagger/index.blade.php` line 159 has a bug where `array_column()` is called on a potentially null value. Fixed by adding `?? []` fallback. This is a bug in the published view from `darkaonline/l5-swagger`, not our code.
- **Storage permissions** — after `composer install`, `storage/` and `bootstrap/cache/` are owned by the wrong user. Must run `chmod -R 775` and `chown -R www-data:www-data` before the app works. Currently a manual step in README — worth baking into Dockerfile later.
- **Swagger docs** — `storage/api-docs/` must be owned by `www-data` after `php artisan l5-swagger:generate`, otherwise the web server can't read the generated JSON.

---

## Decisions / Notes
- Use **Form Requests** for validation (not inline in controller)
- Use **Service class** for business logic (not fat controller)
- Use **API Resources** for response shaping (not manual arrays)
- Use **Mailables** for emails (not raw `Mail::send`)
- `can_edit` logic lives in a dedicated `UserEditPolicy` — keeps it testable and separate from query logic
- Admin email stored in `config/app.php` or `.env` as `ADMIN_EMAIL`
