# Agent Session Guide (Laravel + Vue/Vuetify)

Use this file as the default operating guide for every development session in this repository.

## Stack Baseline

- Backend: Laravel API in repository root.
- Domain modules: `Domain/<Feature>/...` for feature controllers/data/actions (models stay in `app/Models`).
- Shared services: `app/Services` for reusable cross-domain helpers.
- Frontend: Vue + Vuetify app in `frontend/` (see `SETUP_FRONTEND_VUE_VUETIFY.md` for forms, rules, toasts, and API error patterns; register/login live under `frontend/src/pages/Auth/`). Use `@/` imports for `src/` modules.
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
3. Follow the focused setup guide for the touched layer:
   - backend rules in `SETUP_BACKEND_LARAVEL.md`
   - frontend rules in `SETUP_FRONTEND_VUE_VUETIFY.md`
4. Keep changes minimal, typed, and consistent with existing conventions.
5. Run applicable checks (lint, type checks, tests/build when relevant).
6. Summarize what changed, why, and any follow-up verification needed.

## Cross-Stack Best Practices

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
- In Vue SFC files, keep block order as `<template>` then `<script setup>`.
- For frontend API calls, use the shared Axios client at `frontend/src/services/axios.ts`.
- For frontend API typing, prefer generated types from `frontend/src/types/generated.ts` over local duplicated DTO types.
- For forms: type `<v-form>` refs as `VForm` from `vuetify/components`, run `validate()` before submit, use shared rule helpers from `frontend/src/services/rules.ts`, map server errors with `getFieldErrors`, and use toasts for generic failures and backend `message` on success.
- Keep backend responses consistent and easy for frontend consumption.
- Prefer controller class/method injection for shared services over `app(...)` lookups.
- Prefer named HTTP status constants over numeric literals in backend responses.
- Document non-obvious decisions in code comments or task summary.
