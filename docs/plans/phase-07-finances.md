# Fase 7 — Finanzas (movimientos, balance mensual)

**Rama:** `feature/finances-fase-7`  
**Prerrequisito:** Fase 6 mergeada en `main` (pagos de proyecto existen).

---

## Leer primero

1. [WORKFLOW.md](./WORKFLOW.md)
2. [../main/FINANCES.md](../main/FINANCES.md) — **obligatorio**
3. [../main/PROJECTS.md](../main/PROJECTS.md) § ingresos automáticos
4. [../main/UI_ROUTES.md](../main/UI_ROUTES.md) — `/finances`

---

## Paso 0 — Git

```bash
git checkout main && git pull origin main
git checkout -b feature/finances-fase-7
git push -u origin feature/finances-fase-7
```

---

## Paso 1 — Contratos Zod

`packages/contracts/src/finances.ts`:

- `FinanceEntrySchema` — campos según FINANCES.md §3
- `FinanceEntryCreateSchema` — manual: `type`, `amount_cents`, `occurred_on`, `description`, `category`
- `FinanceSummarySchema` — `month`, `total_income_cents`, `total_expense_cents`, `net_cents`, label `surplus|shortfall|balanced`
- Filtros listado: `month`, `type` optional

---

## Paso 2 — Migración tenant

Tabla `finance_entries` según FINANCES.md.

Índice único condicional: evitar duplicar `(source_type, source_id)` cuando no null.

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

## Paso 8 — Cierre PR

Título: `feat: finances module with monthly summary (phase 7)`

---

## Definition of Done

- [ ] `finance_entries` + integración pagos proyecto
- [ ] Ingresos manuales y gastos CRUD
- [ ] Summary mensual correcto
- [ ] Tests + typecheck + manual OK
- [ ] Merge a `main`

---

## No hacer en esta fase

- Rediseño UI premium
- Reversión automática de pagos
