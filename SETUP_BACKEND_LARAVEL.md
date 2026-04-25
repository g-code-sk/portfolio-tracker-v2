# Backend Setup: Laravel API

This repository uses Laravel as the backend API layer.

## Current Backend Context

- Framework: Laravel (API + web routes configured)
- Entry configuration: `bootstrap/app.php`
- API routes: `routes/api.php`
- Domain code location: `Domain/<Feature>/...` (feature controllers, data classes, actions/services)
- Shared/common services location: `app/Services`
- Model location remains: `app/Models`
- TypeScript generation bridge: `app/Providers/TypeScriptTransformerServiceProvider.php`
- Auth endpoints available: `POST /api/register`, `POST /api/login`
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
3. Keep controller methods thin and orchestration-focused.
4. Validate request inputs with Spatie Data classes (request DTO pattern).
5. Return structured API payloads using Spatie Data classes.
6. Place feature-specific controller/data/action classes under `Domain/<Feature>/...`.
7. Place reusable, cross-domain services in `app/Services`.
8. Keep Eloquent models in `app/Models`.
9. Regenerate frontend types when API Data contracts change (`composer types:transform`).
10. Regenerate IDE helper files when model/service surface changes (`composer ide:generate`).

## TypeScript Transformer Scope

- Keep transformer scope aligned with project structure:
  - `app/Data`
  - `Domain` (all feature data classes)
- Add `#[TypeScript]` to backend Data classes that are part of frontend contracts.
- After changing these classes, always run `composer types:transform`.


## Laravel API Best Practices

### Routing and Controllers

- Keep routing explicit and grouped by feature.
- Prefer single-responsibility controllers.
- Use route model binding where possible.
- Version APIs intentionally when contracts change.

### Validation and Data Safety

- Validate all client input with Spatie Data classes (avoid FormRequest/array rules unless there is a strong exception).
- Use Spatie request-to-data-object injection for endpoint input DTOs so validation happens before controller logic.
- Whitelist mass-assignable model fields (`$fillable`), avoid broad `$guarded = []`.
- Normalize/transform data at boundaries before persistence.
- Do not return raw objects or associative arrays from API endpoints; use typed Spatie Data classes.

### Business Logic Organization

- Keep controllers orchestration-focused.
- Place reusable business rules in services/actions.
- Prefer class/method injection for shared services in controllers for clearer typing and fewer IDE false positives.
- Use policies/gates for authorization rules.
- Avoid mixing HTTP concerns with domain logic.

### API Response Design

- Return consistent response shapes across endpoints.
- Use Spatie Data classes for serialization.
- Prefer named HTTP status constants (for example `Response::HTTP_CREATED`) over magic numbers.
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
- When changing response contracts, update frontend consumers accordingly and regenerate TS types.
- Use `variant="outlined"` for frontend form inputs to keep input styling consistent.
- In Vue form field wrapper components, prefer `defineModel` over manual `modelValue` and `update:modelValue` wiring.

## Tool References

- [Spatie Laravel Data](https://spatie.be/docs/laravel-data/v4/introduction)
- [Spatie Request to Data Object](https://spatie.be/docs/laravel-data/v4/as-a-data-transfer-object/request-to-data-object)
- [Laravel IDE Helper](https://github.com/barryvdh/laravel-ide-helper)
- [Spatie TypeScript Transformer](https://spatie.be/docs/typescript-transformer/v3/introduction)
