# Fase 6 — Proyectos, pagos parciales y marcar pagado

**Rama:** `feature/projects-fase-6`  
**Prerrequisito:** Fase 5 mergeada en `main`.

---

## Decisiones obligatorias de diseño

1. **Proyecto hereda historia documental desde quote.**
   - Al convertir quote → project, persistir snapshot mínimo de cliente y referencia de origen (`quote_id`, `quote_number` o equivalente).
   - Billing en Fase 8 debe poder salir del proyecto sin depender del cliente vivo ni de una quote mutable.

2. **IDs siguen convención tenant actual.**
   - `id`, `client_id`, `quote_id`, `project_id` como enteros positivos.

3. **Conversión quote → project debe ser idempotente y exclusiva.**
   - Un `quote_id` solo puede producir un proyecto.
   - `projects.quote_id` debe ser único nullable.

4. **Pagos no crean ingresos en esta fase.**
   - Solo emitir evento o hook idempotente para Fase 7.
   - No insertar `finance_entries` todavía.

5. **Completar proyecto con side effect documental se reserva para Fase 8.**
   - En Fase 6 no exponer una semántica parcial de `complete` que luego cambie contrato.

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

> Si el trabajo lo ejecuta un agente, `commit`/`push`/PR los hace el usuario, no el agente.

---

## Paso 1 — Contratos Zod

`packages/contracts/src/projects.ts`:

### Project

- `id`, `client_id`, `quote_id` nullable (int positivos)
- `name`, `type` enum: `freelance` | `fixed` | `retainer`
- `status` enum: `active` | `on_hold` | `completed` | `cancelled`
- snapshot/referencia:
  - `quote_number` nullable
  - `client_name`, `client_email`, `client_tax_id`, `client_address`
  - `currency`
- `agreed_total_cents`, `paid_total_cents`, `balance_due_cents`
- `is_fully_paid` boolean (derivado)
- fechas: `started_at`, `completed_at` nullable
- timestamps

`packages/contracts/src/payments.ts`:

### ProjectPayment

- `id`, `project_id` (int positivos)
- `amount_cents`, `paid_at` (date)
- `kind` enum: `partial` | `closure` (interno)
- label UI no expuesto — historial usa copy de PROJECTS.md
- `created_at`

Schemas: `RegisterPartialPaymentInput`, `MarkProjectPaidResponse`.

---

## Paso 2 — Migraciones tenant

1. `projects`
   - FK `client_id` → `clients` con `restrictOnDelete`
   - FK `quote_id` → `quotes` nullable con `restrictOnDelete`
   - `quote_id` único nullable para impedir doble conversión
   - snapshot cliente + `quote_number` + `currency`
2. `project_payments`
   - FK `project_id` → `projects` con `cascadeOnDelete`
   - índices sugeridos: `(project_id, paid_at)`, `(project_id, kind)`

FK a `clients`, `quotes` (nullable).

```bash
cd api && php artisan tenants:migrate
```

---

## Paso 3 — Conversión quote → project

`QuoteToProjectService` (transacción):

1. Quote status debe ser `accepted`
2. Verificar que la quote no tenga proyecto previo
3. Crear `Project` con:
   - `agreed_total_cents` = quote `total_cents`
   - snapshot de cliente y referencia de quote
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
5. **No** crear `finance_entry` aún (Fase 7); emitir evento `ProjectPaymentRecorded` o hook equivalente, idempotente

### `markProjectPaid(project, paid_at?)`

1. `amount_to_book = balance_due_cents`
2. Si 0 y ya fully paid → return idempotente
3. Insertar payment kind `closure` por `amount_to_book`
4. Poner paid = agreed, balance = 0
5. Emitir el mismo evento/hook para Fase 7 sin duplicación

Endpoints:

- `POST /projects/{id}/payments` body `{ amount_cents, paid_at? }`
- `POST /projects/{id}/mark-paid` body `{ paid_at? }`
- `GET /projects/{id}/payments`

---

## Paso 5 — Project CRUD + list

- `GET/POST /projects`, `GET/PUT /projects/{id}`

**No exponer `POST /projects/{id}/complete` en esta fase.**  
La transición con side effect documental queda cerrada en Fase 8 para no partir el contrato.

---

## Paso 6 — Tests

`ProjectPaymentTest.php`:

- parcial reduce balance
- varios parciales hasta cero
- mark-paid sin parciales → paid_total = agreed
- mark-paid con parciales → solo restante
- convert quote → project
- convert quote → project idempotente
- project copia snapshot de cliente/quote
- pagos no crean `finance_entries` en Fase 6

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
- [ ] Snapshot de cliente/quote persistido en project
- [ ] Conversión idempotente (1 quote = 1 project)
- [ ] Sin `finance_entries` creados en Fase 6
- [ ] Tests verdes
- [ ] Merge a `main`

---

## No hacer en esta fase

- Entradas en tabla `finance_entries` (Fase 7)
- PDF cuenta de cobro (Fase 8)
- Pulido visual avanzado
