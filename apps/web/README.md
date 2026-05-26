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

## Pruebas

### Unitarias (Vitest)

```bash
cd apps/web && pnpm test:unit
```

### E2E (Playwright)

Prerrequisitos (stack local según ONBOARDING):

1. `/etc/hosts` incluye `127.0.0.1 personal.localhost`
2. API Laravel en **http://personal.localhost:8000**
3. Nuxt en **http://personal.localhost:3000**
4. Tenant `personal.localhost` provisionado con usuario de dev

Configuración:

```bash
cd apps/web
cp .env.e2e.example .env.e2e
# Editar E2E_USER_PASSWORD con la contraseña real del tenant (p. ej. admin@admin.com)
pnpm test:e2e:install   # solo la primera vez (Chromium)
pnpm test:e2e
```

Detalles del harness:

| Aspecto | Valor |
| ------- | ----- |
| Navegador | Chromium |
| `baseURL` | `E2E_BASE_URL` (default `http://personal.localhost:3000`) |
| Auth | `global-setup` → login API → cookie `freelance_auth_token` en el hostname de `E2E_BASE_URL` |
| Datos | fixtures por API tenant (clientes/movimientos únicos por corrida) |
| Finanzas E2E | mes dinámico (año siguiente) + descripciones únicas; assertions por presencia/ausencia, no totales exactos |
| Paralelismo | serial (`workers: 1`) |

Flujos cubiertos en esta fase:

- creación manual de proyecto (`e2e/projects-manual-create.spec.ts`)
- filtros de finanzas por mes/tipo (`e2e/finances-filters.spec.ts`)

## pnpm 11 — build scripts

Allowlist en [`pnpm-workspace.yaml`](../../pnpm-workspace.yaml) → `allowBuilds`. No uses `dangerously-allow-all-builds`. Detalle de paquetes permitidos: ver comentarios en ese archivo y en ONBOARDING.

## UI global

Controles de formulario `w-full` por defecto: [`app/app.config.ts`](app/app.config.ts).
