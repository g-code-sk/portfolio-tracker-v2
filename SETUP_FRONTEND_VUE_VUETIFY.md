# Frontend Setup: Vue + Vuetify

This repository includes a Vue frontend in `frontend/`, designed to consume the Laravel API.

## Current Frontend Context

- Frontend location: `frontend/`
- Stack: Vue 3 + TypeScript + Vite
- UI framework: Vuetify
- HTTP client: Axios via shared client in `frontend/src/services/axios.ts`
- Toasts: `vue3-toastify` (configured in `frontend/src/main.ts`)
- Main app shell: `frontend/src/App.vue`
- Auth screens: `frontend/src/pages/Auth/` (e.g. `LoginPage.vue`, `RegisterPage.vue`)
- Generated backend types: `frontend/src/types/generated.ts`
- Local frontend URL: `http://127.0.0.1:5173` (or next available port)
- Local backend API URL: `http://127.0.0.1:8000`

## Local Setup Checklist

1. Move to frontend directory:
   ```bash
   cd frontend
   ```
2. Install dependencies:
   ```bash
   npm install
   ```
3. Start dev server:
   ```bash
   npm run dev -- --host 127.0.0.1 --port 5173
   ```
4. Confirm frontend can reach backend endpoints.

## Import paths (`@/` alias)

- TypeScript and Vite resolve **`@/`** to **`frontend/src/`** (see `frontend/tsconfig.app.json` `paths` and `frontend/vite.config.ts` `resolve.alias`).
- Prefer **`@/…` imports** for anything under `src/` (components, pages, services, types) so moves between folders do not break long `../` chains.
- Examples: `@/components/AppTextField.vue`, `@/services/axios`, `@/types/generated`, `@/pages/Auth/LoginPage.vue`.

## Daily Development Workflow

1. Keep components focused and small.
2. Put reusable logic in composables.
3. Keep API calls centralized (service modules or composables).
4. Implement loading, empty, and error states for async UI.
5. Verify UX in responsive breakpoints and keyboard navigation.
6. Keep changes focused on core starter functionality.

## Vue + Vuetify Best Practices

### Component Architecture

- Build feature-oriented components, not overly generic abstractions early.
- Keep presentational vs data-fetching concerns separated.
- Prefer explicit props/events over deep implicit coupling.
- Use `script setup` idioms consistently.
- In Vue SFCs, place `<template>` first and `<script setup>` after it.

### State and Data Flow

- Keep local state local; elevate only when shared.
- Derive display data with `computed` instead of duplicating state.
- Avoid mutating props directly.
- Use generated TypeScript types from `frontend/src/types/generated.ts` for API payloads and responses.
- Do not duplicate backend DTO shapes as local inline types when generated types exist.
- If a required type is missing, update backend Data class with `#[TypeScript]`, run `composer types:transform`, then use the generated type.


### API Integration

- Use shared Axios client from `frontend/src/services/axios.ts` for API requests.
- Centralize HTTP handling and error normalization in reusable service/composable layers.
- Type success responses with `ApiSuccessResponse<TData>` from `frontend/src/types/api.ts` (matches backend `message` + optional `data`).
- Map server-side validation to per-field errors with `getFieldErrors` from `frontend/src/services/api-errors.ts` and `FieldErrors<YourPayloadType>`.
- On request failure, show a **generic** user-facing error via toast; do not echo raw backend exception text unless you intentionally allow it in dev only.
- Make API timeout and retry strategy explicit for critical requests.
- Ensure frontend contracts align with Laravel resource responses.

### Forms and validation (reference: `frontend/src/pages/Auth/RegisterPage.vue` and `frontend/src/pages/Auth/LoginPage.vue`)

- **Vuetify `<v-form>`**: hold a ref typed as `import type { VForm } from 'vuetify/components'` and `const formRef = ref<VForm | null>(null)` so `validate()` and `reset()` are typed correctly.
- **Submit flow**: call `const { valid } = await formRef.value.validate()` before any API call; if `!valid`, show a short toast and return.
- **Client rules**: define reusable rule functions in `frontend/src/services/rules.ts` (messages use neutral phrasing like “This field is required.”). Compose field rule arrays **in the page or feature** (e.g. `createRegisterRules` next to the form), not inside the generic rules file, so the service stays reusable across screens.
- **Pass rules** into inputs via a thin wrapper such as `AppTextField` (`variant="outlined"`, `hide-details="auto"`) that forwards `rules` and `error-messages`.
- **Server errors**: after a failed request, assign `fieldErrors` from `getFieldErrors` so Laravel `errors` map shows on the correct fields alongside client rules.
- **Success**: toast the backend `message` from `ApiSuccessResponse`, then clear or reset the form with `formRef.value?.reset()` when appropriate.
- **Layout**: prefer flex + gap utilities (e.g. `d-flex flex-column ga-3`) on the form instead of stacking `mb-*` on every control.

### Vuetify Usage

- Use built-in spacing and layout utilities for consistency.
- Favor theme tokens over hardcoded colors.

### Performance

- Avoid unnecessary reactive sources in large component trees.
- Split large views into lazy-loaded route chunks where reasonable.
- Memoize expensive derived data with `computed`.
- Avoid excessive watchers when computed properties are enough.

## Useful Commands

```bash
# in frontend/
npm run dev -- --host 127.0.0.1 --port 5173
npm run build
npm run lint
```

## Backend Contract Notes (Frontend-facing)

- Align request/response contracts with backend API resources.
- Treat backend as the source of truth for field names and payload shape.
- Treat backend validation errors (`422`) as first-class UI states.
- Coordinate any field rename or payload shape change with backend updates.

## Tool References

- [Spatie Laravel Data](https://spatie.be/docs/laravel-data/v4/introduction)
- [Spatie Request to Data Object](https://spatie.be/docs/laravel-data/v4/as-a-data-transfer-object/request-to-data-object)
- [Spatie TypeScript Transformer](https://spatie.be/docs/typescript-transformer/v3/introduction)
- [Vuetify Form (validate, reset, exposed properties)](https://vuetifyjs.com/en/components/forms/)
