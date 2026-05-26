---
description: Freelance System — reglas para agentes de código
alwaysApply: true
---

# AGENTS.md

## Objective

Mantener Freelance System coherente: multi-tenant (Stancl), dinero en centavos, contratos API estables y frontend modular sobre Nuxt UI Dashboard.

## Authoritative references (read before coding)

1. `docs/main/ARCHITECTURE.md`
2. `docs/main/TENANCY.md`
3. `docs/main/ENGINEERING_GUARDRAILS.md`
4. `docs/plans/README.md` — índice general; `docs/plans/WORKFLOW.md` para Git
5. `docs/plans/mvp/README.md` y `docs/plans/mvp/bootstrap.md` — fases MVP e índice histórico 0–2
6. `docs/main/UI_ROUTES.md` — rutas y vistas (no modales como CRUD principal)
7. `docs/main/FRONTEND_ARCHITECTURE.md` — patrón obligatorio frontend/Nuxt UI
8. `packages/contracts/README.md`

## Mandatory skill routing

Cargar y seguir cuando aplique:

| Skill                            | Cuándo                                                   |
| -------------------------------- | -------------------------------------------------------- |
| `vue-best-practices`             | Cualquier `.vue`, composable, Pinia, Nuxt UI             |
| `laravel-specialist`             | Backend Laravel                                          |
| `freelance-monetary-consistency` | Dinero, centavos, IVA opcional, totales, PDF monetario   |
| `freelance-tenancy-stancl`       | Stancl, rutas central/tenant, migrate, jobs, provision   |
| `freelance-document-workflow`    | Cotización → proyecto → pagos → cuenta de cobro          |
| `modern-web-guidance`            | HTML/CSS, forms, dialogs, layout — **antes** de UI nueva |
| `frontend-design`                | Dirección visual y calidad de interfaces                 |
| `accessibility`                  | a11y en formularios, contraste, teclado                  |
| `typescript-advanced-types`      | Contratos en `packages/contracts`, bordes de composables |

## Stack decisions (do not contradict)

- **MySQL 8** — central + BD por tenant
- **Stancl Tenancy** — no reimplementar bootstrap/migrate
- **Sanctum Bearer** — no asumir cookie SPA salvo cambio documentado
- **Nuxt 4** en `apps/web` — template dashboard; `ssr: false`
- **IVA** — motor en `MoneyMath`; `tax_enabled` default `false`

## Change safety contract

1. Preservar el patrón del módulo tocado.
2. Diff mínimo; no batch fixes no relacionados.
3. No romper contratos públicos (API, props, DB semantics).
4. Cambio de contrato → primero `packages/contracts`.
5. Ediciones inesperadas en archivos → detenerse y preguntar.
6. **Prohibido ejecutar `git commit`, `git push`, o abrir PRs desde un agente.** El agente solo prepara cambios locales y evidencia de validación; la publicación la hace el usuario.
7. **No pedir excepción para publicar cambios.** Aunque la validación esté en verde, el agente no debe commitear ni pushear.
8. **La validación sigue siendo obligatoria antes de dar un cambio por listo.** Ejecutar `nuxi typecheck` / `php artisan test` / `validate-touched-files.sh` según aplique.

## Tenancy boundary (Stancl + central/tenant)

1. Rutas **tenant** en `routes/tenant.php` con middleware tenancy.
2. Rutas **central** en `routes/api.php` sin modelos tenant salvo `$tenant->run()`.
3. No consultar tablas de negocio desde contexto central.
4. Jobs con side effects en BD tenant → tenant-aware.
5. Producción: tenant por **subdominio** (ver `TENANCY.md`).

## Monetary guardrails

1. Centavos/minor units en servicios y BD.
2. UI solo formatea.
3. Sin float para dinero.
4. Sin `* 1.21` (u otro IVA) fuera de `MoneyMath`.
5. Si `tax_enabled` es false → tasas 0; recalcular totales en backend.

## Backend guardrails

1. Controllers delgados; reglas en Application services.
2. Form Requests = validación entrada.
3. Orquestador con 3+ responsabilidades → extraer Action/Support.
4. API Resources alineados a `packages/contracts`.

## Frontend guardrails

1. Taxonomía: `useXApi` → hydrate/serialize → `useX` orquestador → componentes tontos.
2. Pinia solo `auth` + `tenant` (+ loader si existe).
3. Sin negocio monetario definitivo en composables.
4. Hub HTTP: `composables/api/useApi.ts`.
5. Composable > ~250 LOC → revisar split; > ~500 → plan obligatorio.
6. Antes de UI nueva, leer `docs/main/FRONTEND_ARCHITECTURE.md`.
7. No usar `UInput type="date"`; para fechas usar `UInputDate` de Nuxt UI.
8. No introducir componentes Nuxt UI “de memoria”; validar en docs oficiales o MCP primero.
9. **Nunca toast genérico sin parsear error API.** Usar `useApiError().toastApiError(error, { fallback })` en todo catch.

## API contract rules

1. Schema Zod (o OpenAPI) en `packages/contracts` es la fuente de verdad JSON.
2. No duplicar shapes TS a mano si existe schema.
3. Versionado `/api/v1`; breaking → v2.

## Validation gate

Tras tocar código:

```bash
bash scripts/validate-touched-files.sh <files...>
```

- PHP: `php -l` en archivos tocados
- Cuando exista `apps/web`: `pnpm exec nuxi typecheck` si hay TS/Vue

No declarar validación OK sin comando y salida.

## Sensitive data

- No commitear `.env`, tokens, claves.
- Solo `*.example` en git.

## Communication

1. Español neutro (LatAm) por defecto.
2. Respuestas concisas: **Change / Cause / Files / Validation / Next**.
3. Mantener nombres técnicos (Stancl, Sanctum, etc.) sin traducir.
