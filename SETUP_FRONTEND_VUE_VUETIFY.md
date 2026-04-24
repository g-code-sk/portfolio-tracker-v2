# Frontend Setup: Vue + Vuetify

This repository includes a Vue frontend in `frontend/`, designed to consume the Laravel API.

## Current Frontend Context

- Frontend location: `frontend/`
- Stack: Vue 3 + TypeScript + Vite
- UI framework: Vuetify
- HTTP client: Axios via shared client in `frontend/src/services/axios.ts`
- Main app shell: `frontend/src/App.vue`
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
- Make API timeout and retry strategy explicit for critical requests.
- Ensure frontend contracts align with Laravel resource responses.

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
