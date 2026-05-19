# Plan de bootstrap

Checklist para pasar de documentación (fase 0) a desarrollo feature. **No saltar fases.**

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

## Fase 4 — Contratos + Clients (patrón dorado)

- [ ] Schema Zod `Client` en `packages/contracts`
- [ ] Migración `clients` tenant
- [ ] `ClientService`, controller, resource
- [ ] Composables + página CRUD
- [ ] Test feature tenant + test unit composable

---

## Fases 5–9 — Dominio

| Fase | Módulo                                  |
| ---- | --------------------------------------- |
| 5    | Quotes + PDF básico                     |
| 6    | Quote → Project + payments              |
| 7    | Ledger (fuente obligatoria en ingresos) |
| 8    | Billing on complete + email job         |
| 9    | Plantillas custom + UI `tax_enabled`    |

---

## Decisiones bloqueadas

| Tema    | Valor                     |
| ------- | ------------------------- |
| BD      | MySQL 8                   |
| Auth    | Sanctum Bearer            |
| Tenancy | Stancl, BD por tenant     |
| IVA     | Motor sí; default apagado |
