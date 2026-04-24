# Backend Setup: Laravel API

This repository uses Laravel as the backend API layer.

## Current Backend Context

- Framework: Laravel (API + web routes configured)
- Entry configuration: `bootstrap/app.php`
- API routes: `routes/api.php`
- Typed API data layer: `app/Data` via `spatie/laravel-data`
- TypeScript generation bridge: `app/Providers/TypeScriptTransformerServiceProvider.php`
- Current API example endpoint: `GET /api/test-data`
- Backend local URL: `http://127.0.0.1:8000`

## Local Setup Checklist

1. Install PHP dependencies:
   ```bash
   composer install
   ```
2. Create environment file:
   ```bash
   cp .env.example .env
   ```
3. Generate app key:
   ```bash
   php artisan key:generate
   ```
4. Configure database credentials in `.env`.
5. Run migrations (if needed):
   ```bash
   php artisan migrate
   ```
6. Start backend server:
   ```bash
   php artisan serve --host=127.0.0.1 --port=8000
   ```

## Daily Development Workflow

1. Pull latest changes and install dependencies when lock files change.
2. Update `routes/api.php` for new endpoints.
3. Keep controller methods thin; move domain logic into service classes, but only if it makes sense, don't overdo it.
4. Validate request inputs with Spatie Data classes.
5. Return structured API payloads with Spatie Data classes.
6. Use Laravel IDE helper to have proper type hinting
7. Transform backend Data classes to frontend TypeScript types.


## Laravel API Best Practices

### Routing and Controllers

- Keep routing explicit and grouped by feature.
- Prefer single-responsibility controllers.
- Use route model binding where possible.
- Version APIs intentionally when contracts change.

### Validation and Data Safety

- Validate all client input with Spatie Data classes (avoid FormRequest/array rules unless there is a strong exception).
- Whitelist mass-assignable model fields (`$fillable`), avoid broad `$guarded = []`.
- Normalize/transform data at boundaries before persistence.
- Do not return raw objects or associative arrays from API endpoints; use typed Spatie Data classes.

### Business Logic Organization

- Keep controllers orchestration-focused.
- Place reusable business rules in services/actions.
- Use policies/gates for authorization rules.
- Avoid mixing HTTP concerns with domain logic.

### API Response Design

- Return consistent response shapes across endpoints.
- Use Spatie Data classes for serialization.
- Include pagination metadata for list endpoints.
- Use clear, stable error payloads for frontend handling.

### Error Handling and Observability

- Map domain errors to meaningful HTTP status codes.
- Do not leak stack traces or internal details in production.
- Log contextual metadata for important failures.
- Keep logs structured and actionable.

### Security

- Enforce auth on protected routes with middleware.
- Validate and sanitize user-provided content.
- Keep secrets only in environment variables.

## Useful Commands

```bash
# inspect routes
php artisan route:list

# transform backend PHP types to frontend TS types
composer types:transform

# regenerate IDE helper files
composer ide:generate
```

## Integration Notes for Frontend

- Frontend app lives in `frontend/` and calls backend in local development.
- Keep API field names stable; coordinate schema changes with frontend updates.
- When changing response contracts, update frontend consumers accordingly.

## Tool References

- [Spatie Laravel Data](https://spatie.be/docs/laravel-data/v4/introduction)
- [Laravel IDE Helper](https://github.com/barryvdh/laravel-ide-helper)
- [Spatie TypeScript Transformer](https://spatie.be/docs/typescript-transformer/v3/introduction)
