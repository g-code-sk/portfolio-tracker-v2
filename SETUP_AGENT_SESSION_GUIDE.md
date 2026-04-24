# Agent Session Guide (Laravel + Vue/Vuetify)

Use this file as the default operating guide for every development session in this repository.

## Stack Baseline

- Backend: Laravel API in repository root.
- Frontend: Vue + Vuetify app in `frontend/`.
- API route source: `routes/api.php`.
- App bootstrap and route registration: `bootstrap/app.php`.

## Session Start Checklist

1. Read task scope and confirm acceptance criteria.
2. Check current workspace state before editing (`git status`).
3. Inspect relevant existing files before proposing changes.
4. Prefer smallest safe change that satisfies requirements.
5. Keep backend and frontend contracts aligned.

## Execution Protocol (Default)

1. Understand request and identify touched layers (backend, frontend, both).
2. Locate related code paths and data contracts.
3. Implement with typed contracts first: prefer Spatie Data classes for backend payloads.
4. Regenerate frontend types from backend Data classes (`composer types:transform`) after API contract changes.
5. Regenerate IDE helper files when model/service surface changes (`composer ide:generate`).
6. Run applicable checks (lint, type checks, build when relevant).
7. Summarize what changed, why, and any follow-up verification needed.

## Cross-Stack Best Practices

### API Contract Discipline

- Treat API payload shape as a versioned contract.
- Keep naming stable and consistent across backend/frontend.
- When changing schema, update both producers and consumers in the same session.
- Capture validation and error semantics consistently.
- Use Spatie Data classes for both request validation and API responses.
- Avoid raw objects/associative arrays for request/response contracts.

### Clean Architecture and Ownership

- Controllers/components orchestrate; services/composables hold reusable logic.
- Avoid duplicate logic across backend and frontend boundaries.
- Keep modules small and purpose-driven.
- Prefer explicit dependencies over hidden global coupling.

### Security and Reliability

- Validate all backend input; do not trust client payloads.
- Enforce authorization close to protected operations.
- Avoid exposing secrets or internal implementation details.
- Handle failures with predictable error states and logs.

### Code Review Readiness

- Keep diffs focused and explain rationale in commit/PR messaging.
- Avoid unrelated refactors in the same change unless required.
- Ensure naming, formatting, and conventions match nearby code.
- Leave clear notes for known trade-offs or deferred tasks.

## Practical Command Reference

```bash
# backend (repo root)
php artisan serve --host=127.0.0.1 --port=8000
composer types:transform
composer ide:generate

# frontend
cd frontend
npm install
npm run dev -- --host 127.0.0.1 --port 5173
npm run lint
npm run build
```

## Working Agreement for This Repo

- Default to concise, maintainable implementations.
- Keep frontend UX resilient for loading, empty, and error states.
- Keep backend responses consistent and easy for frontend consumption.
- Document non-obvious decisions in code comments or task summary.
