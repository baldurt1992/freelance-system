# Fase 6 — Proyectos, pagos parciales y marcar pagado

**Rama:** `feature/projects-fase-6`  
**Prerrequisito:** Fase 5 mergeada en `main`.

---

## Leer primero

1. [WORKFLOW.md](./WORKFLOW.md)
2. [../main/PROJECTS.md](../main/PROJECTS.md) — **obligatorio**
3. [../main/UI_ROUTES.md](../main/UI_ROUTES.md) — `/projects/[id]`
4. [.agents/skills/freelance-document-workflow/SKILL.md](../../.agents/skills/freelance-document-workflow/SKILL.md)

---

## Paso 0 — Git

```bash
git checkout main && git pull origin main
git checkout -b feature/projects-fase-6
git push -u origin feature/projects-fase-6
```

---

## Paso 1 — Contratos Zod

`packages/contracts/src/projects.ts`:

### Project

- `id`, `client_id`, `quote_id` nullable
- `name`, `type` enum: `freelance` | `fixed` | `retainer`
- `status` enum: `active` | `on_hold` | `completed` | `cancelled`
- `agreed_total_cents`, `paid_total_cents`, `balance_due_cents`
- `is_fully_paid` boolean (derivado)
- fechas: `started_at`, `completed_at` nullable
- timestamps

`packages/contracts/src/payments.ts`:

### ProjectPayment

- `id`, `project_id`
- `amount_cents`, `paid_at` (date)
- `kind` enum: `partial` | `closure` (interno)
- label UI no expuesto — historial usa copy de PROJECTS.md

Schemas: `RegisterPartialPaymentInput`, `MarkProjectPaidResponse`.

---

## Paso 2 — Migraciones tenant

1. `projects`
2. `project_payments`

FK a `clients`, `quotes` (nullable).

```bash
cd api && php artisan tenants:migrate
```

---

## Paso 3 — Conversión quote → project

`QuoteToProjectService` (transacción):

1. Quote status debe ser `accepted`
2. Crear `Project` con `agreed_total_cents` = quote `total_cents`
3. `paid_total_cents` = 0, `balance_due_cents` = agreed_total
4. Quote → `converted`
5. Idempotencia: no convertir dos veces

Endpoint: `POST /quotes/{id}/convert-to-project` → devuelve Project.

---

## Paso 4 — Pagos

`ProjectPaymentService`:

### `registerPartialPayment(project, amount_cents, paid_at?)`

1. Validar `amount_cents` > 0 y `<= balance_due_cents`
2. Insertar `project_payments` kind `partial`
3. Recalcular `paid_total_cents`, `balance_due_cents`
4. Si `balance_due_cents === 0` → `is_fully_paid` true
5. **No** crear `finance_entry` aún (Fase 7); dejar hook/comentario `// FinanceEntryListener phase 7` o evento `ProjectPaymentRecorded`

### `markProjectPaid(project, paid_at?)`

1. `amount_to_book = balance_due_cents`
2. Si 0 y ya fully paid → return idempotente
3. Insertar payment kind `closure` por `amount_to_book`
4. Poner paid = agreed, balance = 0

Endpoints:

- `POST /projects/{id}/payments` body `{ amount_cents, paid_at? }`
- `POST /projects/{id}/mark-paid` body `{ paid_at? }`
- `GET /projects/{id}/payments`

---

## Paso 5 — Project CRUD + list

- `GET/POST /projects`, `GET/PUT /projects/{id}`
- `POST /projects/{id}/complete` → status `completed` (billing Fase 8 después)

---

## Paso 6 — Tests

`ProjectPaymentTest.php`:

- parcial reduce balance
- varios parciales hasta cero
- mark-paid sin parciales → paid_total = agreed
- mark-paid con parciales → solo restante
- convert quote → project

```bash
cd api && php artisan test --filter=Project
```

---

## Paso 7 — Frontend `/projects/[id]`

### Estructura (ver UI_ROUTES.md)

- `pages/projects/index.vue` — badge Por cobrar / Pagado totalmente
- `pages/projects/[id].vue` — orquestador
- `components/Projects/sections/ProjectHeader.vue`
- `components/Projects/sections/ProjectPaymentsCard.vue`:
  - Total / Cobrado / Por cobrar
  - input monto + botón **Registrar pago parcial**
  - botón **Marcar como pagado** + `<dialog>` confirmación
  - historial pagos
- `components/Projects/sections/ProjectSummary.vue`

### Composables

- `useProjectsApi.ts`, `useProjectPayments.ts`

Menú **Proyectos** → `/projects`

```bash
pnpm typecheck:web
```

**Manual:** convertir cotización aceptada → proyecto → parcial → mark-paid en otro proyecto sin parciales.

---

## Paso 8 — Cierre PR

Título: `feat: projects and partial payments (phase 6)`

---

## Definition of Done

- [ ] Convert quote → project
- [ ] Pagos parciales + mark-paid según PROJECTS.md
- [ ] Vista `/projects/[id]` completa (no modal)
- [ ] Tests verdes
- [ ] Merge a `main`

---

## No hacer en esta fase

- Entradas en tabla `finance_entries` (Fase 7)
- PDF cuenta de cobro (Fase 8)
- Pulido visual avanzado
