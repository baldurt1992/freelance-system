# Plan de bootstrap

Índice histórico de fases 0–2 (completadas). **Runbooks ejecutables por fase:** [README.md](./README.md).

| Siguiente trabajo | Plan                                         |
| ----------------- | -------------------------------------------- |
| Git en cada fase  | [../WORKFLOW.md](../WORKFLOW.md)             |
| Fase 4            | [phase-04-clients.md](./phase-04-clients.md) |

**No saltar fases.** UI polish global después de fase 9.

---

## Fase 0 — Documentación ✅

- [x] `docs/main/ARCHITECTURE.md`
- [x] `docs/main/TENANCY.md`
- [x] `docs/main/ENGINEERING_GUARDRAILS.md`
- [x] `docs/main/ONBOARDING.md`
- [x] `AGENTS.md`
- [x] `.agents/skills/*` (esqueletos)
- [x] `packages/contracts/README.md`
- [x] `.cursor/rules/*` (`freelance-architecture`, `freelance-tenancy`)
- [x] `scripts/validate-touched-files.sh`, `pnpm-workspace.yaml`, `.gitignore`
- [x] `README.md` raíz

---

## Fase 1 — API Laravel + Stancl ✅

- [x] `composer create-project laravel/laravel api` (Laravel **13** skeleton)
- [x] `composer require stancl/tenancy laravel/sanctum spatie/laravel-permission`
- [x] `php artisan tenancy:install`
- [x] MySQL documentado en `api/.env.example`
- [x] `routes/api.php` (central) y `routes/tenant.php` (tenant)
- [x] `InitializeTenancyByDomain` en rutas tenant
- [x] Migraciones tenant: users, sanctum, permission
- [x] `Support/Money/MoneyMath.php` + tests unitarios
- [x] Settings tenant en `data`: `tax_enabled`, `currency`
- [x] Comando `tenant:provision`
- [x] `POST /api/v1/auth/login`, `GET /api/v1/auth/me`, `POST /api/v1/auth/logout`
- [x] `GET /api/v1/central/health`
- [x] `php artisan test` (7 tests)
- [x] Redis cache (`CACHE_STORE=redis`, `CacheTenancyBootstrapper`)
- [x] `docker-compose.dev.yml` (redis + mysql opcional)

**Validación local:** ver `api/README.md` — `migrate` + `tenant:provision personal` + login en `personal.localhost`.

---

## Fase 1b — Redis ✅

- [x] `.env` / `.env.example`: `CACHE_STORE=redis`, `REDIS_CLIENT=phpredis`
- [x] Documentación en `TENANCY.md` §10
- [x] `docker-compose.dev.yml` (servicio redis)

---

## Fase 2 — Nuxt dashboard ✅

- [x] Crear `apps/web` desde template `ui/dashboard`
- [x] `pnpm-workspace.yaml` + root `package.json` (`dev:web`, `typecheck:web`)
- [x] `ssr: false`, `NUXT_PUBLIC_API_BASE_URL`
- [x] `composables/api/useApi.ts` (Bearer, ofetch)
- [x] `stores/auth.ts`, `stores/tenant.ts`
- [x] Layout del template + página `/login` + dashboard home
- [x] `server/api/*` stubs vacíos (sin mocks de negocio)

**Validación:** `cd apps/web && nuxt typecheck`, login en `http://personal.localhost:3000/login`

---

## Fase 3 — Infra dev (parcial)

- [x] `docker-compose.dev.yml` (redis + mysql; api/web en host)
- [x] `.env.example` raíz (índice) + `api/.env.example` + `apps/web/.env.example`
- [x] `ONBOARDING.md` con comandos reales (puerto 8000, hosts, pnpm)
- [x] `scripts/setup-dev-hosts.sh` + `setup-dev-hosts.ps1`
- [ ] Contenedores api + web en Docker (opcional)
- [ ] `scripts/validate-touched-files.sh` (ya existe; ampliar cuando haya CI)

---

## Fases 4–9 — Ver planes detallados

| Fase     | Plan                                                             |
| -------- | ---------------------------------------------------------------- |
| 3 (opc.) | [phase-03-infra-docker.md](./phase-03-infra-docker.md)           |
| 4        | [phase-04-clients.md](./phase-04-clients.md)                     |
| 5        | [phase-05-quotes.md](./phase-05-quotes.md)                       |
| 6        | [phase-06-projects-payments.md](./phase-06-projects-payments.md) |
| 7        | [phase-07-finances.md](./phase-07-finances.md)                   |
| 8        | [phase-08-billing.md](./phase-08-billing.md)                     |
| 9        | [phase-09-templates-tax.md](./phase-09-templates-tax.md)         |

---

## Decisiones bloqueadas

| Tema    | Valor                     |
| ------- | ------------------------- |
| BD      | MySQL 8                   |
| Auth    | Sanctum Bearer            |
| Tenancy | Stancl, BD por tenant     |
| IVA     | Motor sí; default apagado |
