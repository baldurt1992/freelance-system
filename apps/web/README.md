# `@freelance/web`

Nuxt 4 SPA (`ssr: false`) — [Nuxt UI Dashboard](https://github.com/nuxt-ui-templates/dashboard).

Setup completo del monorepo (hosts, API, Redis, MySQL): **[docs/main/ONBOARDING.md](../../docs/main/ONBOARDING.md)**.

## Solo esta app

```bash
# Desde la raíz del monorepo
pnpm install
cp apps/web/.env.example apps/web/.env
pnpm dev:web
```

Abrir: **http://personal.localhost:3000/login** (no uses `localhost:3000`).

## Variables

| Variable                   | Ejemplo                                 |
| -------------------------- | --------------------------------------- |
| `NUXT_PUBLIC_API_BASE_URL` | `http://personal.localhost:8000/api/v1` |

## Scripts (raíz del monorepo)

```bash
pnpm dev:web
pnpm typecheck:web
pnpm build:web
```

## pnpm 11 — build scripts

Allowlist en [`pnpm-workspace.yaml`](../../pnpm-workspace.yaml) → `allowBuilds`. No uses `dangerously-allow-all-builds`. Detalle de paquetes permitidos: ver comentarios en ese archivo y en ONBOARDING.

## UI global

Controles de formulario `w-full` por defecto: [`app/app.config.ts`](app/app.config.ts).
