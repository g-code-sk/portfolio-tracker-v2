# Frontend Setup: Vue + Vuetify

This repository includes a Vue frontend in `frontend/`, designed to consume the Laravel API.

## Current Frontend Context

- Frontend location: `frontend/`
- Stack: Vue 3 + TypeScript + Vite
- UI framework: Vuetify
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

### State and Data Flow

- Keep local state local; elevate only when shared.
- Derive display data with `computed` instead of duplicating state.
- Avoid mutating props directly.
- Use generated TypeScript types from backend Data classes instead of manually duplicating API types.
- If generated types are unavailable for a specific case, define temporary local types and schedule cleanup.


### API Integration

- Centralize HTTP handling and error normalization.
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

# in repo root, regenerate frontend types from backend data classes
composer types:transform
```

## Integration Notes for Laravel Backend

- Align request/response contracts with backend API resources.
- Assume backend request validation and response serialization are both defined by Spatie Data classes.
- Treat backend validation errors (`422`) as first-class UI states.
- Coordinate any field rename or payload shape change with backend updates.

## Tool References

- [Spatie Laravel Data](https://spatie.be/docs/laravel-data/v4/introduction)
- [Spatie TypeScript Transformer](https://spatie.be/docs/typescript-transformer/v3/introduction)
