# Fase 7 — Finanzas (movimientos, balance mensual)

**Rama:** `feature/finances-fase-7`  
**Prerrequisito:** Fase 6 mergeada en `main` (pagos de proyecto existen).

---

## Decisiones obligatorias de diseño

1. **Finanzas es libro cash-basis derivado de pagos reales.**
   - `occurred_on` de ingresos automáticos sale de `paid_at` del pago.
   - Cuenta de cobro emitida no genera ingreso.

2. **IDs siguen convención tenant actual.**
   - `finance_entries.id` y `source_id` usan enteros positivos cuando referencian recursos internos.

3. **Entradas automáticas son inmutables.**
   - Si `is_manual = false`, no editar ni borrar desde UI.
   - Correcciones futuras se resuelven con reversión explícita del origen, no con edición contable manual.

4. **Idempotencia es obligatoria.**
   - Un `project_payment` produce como máximo una `finance_entry`.

---

## Leer primero

1. [../WORKFLOW.md](../WORKFLOW.md)
2. [../../main/FINANCES.md](../../main/FINANCES.md) — **obligatorio**
3. [../../main/PROJECTS.md](../../main/PROJECTS.md) § ingresos automáticos
4. [../../main/UI_ROUTES.md](../../main/UI_ROUTES.md) — `/finances`
5. [../../main/ENGINEERING_GUARDRAILS.md](../../main/ENGINEERING_GUARDRAILS.md)
6. [../../main/TENANCY.md](../../main/TENANCY.md)
7. [../../main/FRONTEND_ARCHITECTURE.md](../../main/FRONTEND_ARCHITECTURE.md)

---

## Guardrails transversales (obligatorios)

1. Todas las rutas en `api/routes/tenant.php` bajo `auth:sanctum`; nada de dominio central para entidades tenant.
2. Cambio de shape: primero `packages/contracts`, luego Form Request + Resource + composables.
3. Montos siempre en centavos y cálculo definitivo en backend; UI solo formatea.
4. En frontend, todo `catch` usa `useApiError().toastApiError(error, { fallback })`; 422 debe mapearse por campo cuando aplique.
5. Listas de finanzas deben mantener coherencia visual/UX con baseline `clients` (búsqueda, paginación, estado vacío o justificación explícita).

---

## Paso 0 — Git

```bash
git checkout main && git pull origin main
git checkout -b feature/finances-fase-7
git push -u origin feature/finances-fase-7
```

> Si el trabajo lo ejecuta un agente, `commit`/`push`/PR los hace el usuario, no el agente.

---

## Paso 1 — Contratos Zod

`packages/contracts/src/finances.ts`:

- `FinanceEntrySchema` — campos según FINANCES.md §3
- `FinanceEntryCreateSchema` — manual: `type`, `amount_cents`, `occurred_on`, `name`, `description?`, `category`
- `FinanceSummarySchema` — `month`, `total_income_cents`, `total_expense_cents`, `net_cents`, label `surplus|shortfall|balanced`
- Filtros listado: `month`, `type` optional
- `id` y `source_id` tipados según convención actual de enteros positivos
- `is_manual` expuesto explícitamente en el contrato
- `occurred_on` en formato date `YYYY-MM-DD`

---

## Paso 2 — Migración tenant

Tabla `finance_entries` según FINANCES.md.

Índice único: evitar duplicar `(source_type, source_id)` cuando no null.

Campos clave:

- `id` int positivo
- `source_id` unsigned bigint nullable
- `is_manual` bool
- índices: `(occurred_on)`, `(type, occurred_on)`, unique `(source_type, source_id)`

```bash
cd api && php artisan tenants:migrate
```

---

## Paso 3 — FinanceEntryService

Métodos:

- `createManualEntry(...)` — income o expense, `is_manual=true`
- `createFromProjectPayment(ProjectPayment $payment)` — income, `source_type=project_payment`, idempotente
- `updateManualEntry`, `deleteManualEntry` — solo si `is_manual`
- `summaryForMonth(YYYY-MM)` — agregaciones SQL
- `createFromProjectPayment` debe usar `paid_at` como `occurred_on`

---

## Paso 4 — Integrar con pagos (Fase 6)

En `ProjectPaymentService` (o listener `ProjectPaymentRecorded`):

1. Tras **cada** `registerPartialPayment` → llamar `createFromProjectPayment`
2. Tras **mark-paid** (closure) → llamar `createFromProjectPayment` solo por monto del closure

**Verificar:** no duplicar si se reintenta request.

---

## Paso 5 — API HTTP

Rutas `auth:sanctum` en `tenant.php`:

| Método | Ruta                       |
| ------ | -------------------------- |
| GET    | `/finances/summary?month=` |
| GET    | `/finances/entries`        |
| POST   | `/finances/entries`        |
| GET    | `/finances/entries/{id}`   |
| PATCH  | `/finances/entries/{id}`   |
| DELETE | `/finances/entries/{id}`   |

Controller delgado → Service.

---

## Paso 6 — Tests

`FinanceEntryTest.php`:

- pago parcial proyecto crea 1 income
- mark-paid sin parciales → 1 income por total
- mark-paid con parciales → 1 income por restante
- gasto manual en mes → summary net correcto
- ingreso manual (rifa) en summary
- no duplicar finance entry mismo payment id
- auto entry no editable / no borrable
- `occurred_on` de auto income = `paid_at`

```bash
cd api && php artisan test --filter=Finance
```

---

## Paso 7 — Frontend

### Páginas

- `pages/finances/index.vue` — tabs: **Resumen** | **Ingresos** | **Gastos**
  - Resumen: selector mes + cards totales + neto (Sobrante/Faltante)
  - Ingresos/Gastos: tablas + botón → `/finances/entries/new?type=income|expense`
- `pages/finances/entries/new.vue` — formulario página completa
- `pages/finances/entries/[id]/edit.vue` — solo manuales

### Composables

- `useFinancesApi.ts`, `useFinanceSummary.ts`

Menú **Finanzas** → `/finances`

```bash
pnpm typecheck:web
```

**Manual:** parcial en proyecto → ver ingreso en Finanzas mismo mes; crear gasto AI; crear ingreso rifa; ver balance.

---

## Paso 7.5 — Validación obligatoria

```bash
bash scripts/validate-touched-files.sh <archivos_fase7...>
cd api && php artisan test --filter=Finance
pnpm --filter @freelance/web exec nuxi typecheck
```

---

## Paso 8 — Cierre PR

Título: `feat: finances module with monthly summary (phase 7)`

---

## Definition of Done

- [x] `finance_entries` + integración pagos proyecto
- [x] Ingresos manuales y gastos CRUD
- [x] Summary mensual correcto
- [x] Entradas automáticas inmutables
- [x] Idempotencia por `project_payment`
- [x] Tests + typecheck + validación OK en local
- [x] Merge a `main`

## Estado actual

Implementado en `feature/finances-fase-7` y mergeado a `main` a fecha **2026-05-25**.

Validación ejecutada en local:

```bash
bash scripts/validate-touched-files.sh <archivos_fase7...>
cd api && php artisan test --filter=Finance
cd api && php artisan test --filter=Project
pnpm --filter @freelance/web exec nuxi typecheck
```

Fase cerrada en `main` mediante PR `#5`.

---

## No hacer en esta fase

- Rediseño UI premium
- Reversión automática de pagos
