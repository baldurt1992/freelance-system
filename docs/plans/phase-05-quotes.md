# Fase 5 — Cotizaciones + PDF básico

**Rama:** `feature/quotes-fase-5`  
**Prerrequisito:** Fase 4 mergeada en `main` (`clients` + `packages/contracts`).  
**Recomendado antes o al inicio:** [Fase 4.5 — Manejo de errores](./phase-04.5-error-handling.md) (evita repetir toasts genéricos en cotizaciones).

---

## Decisiones obligatorias de diseño

Antes de implementar, asumir estas reglas como **cerradas**:

1. **La cotización es documento histórico, no vista viva del cliente.**
   - Al crearla, debe guardar snapshot mínimo de cliente: `client_name`, `client_email`, `client_tax_id`, `client_address`.
   - El PDF siempre sale desde el snapshot persistido, no desde el cliente actual.

2. **Totales y fiscalidad se calculan solo en backend.**
   - El frontend puede enviar líneas y `tax_rate`, pero **no** se confía en `subtotal_cents`, `tax_cents`, `total_cents` derivados por cliente.
   - Backend recalcula con `MoneyMath` según `tax_enabled`.

3. **La cotización debe ser trazable como documento comercial.**
   - Incluir `number` único por tenant.
   - Incluir `currency` snapshot.
   - Incluir timestamps de transición: `sent_at`, `accepted_at`, `rejected_at`.

4. **IDs internos siguen el patrón actual del sistema tenant.**
   - `id`, `client_id`, `quote_id` como enteros positivos.
   - No introducir UUID solo para quotes si clients y resto del dominio siguen con enteros.

5. **La relación cliente → cotización no debe borrar historia.**
   - `quotes.client_id` debe usar `restrictOnDelete`.
   - `quote_lines.quote_id` sí usa `cascadeOnDelete`.

6. **Descarga PDF autenticada sin token en URL.**
   - En frontend se resuelve con `fetch`/blob autenticado.
   - No pasar Bearer en query string.

---

## Leer primero

1. [WORKFLOW.md](./WORKFLOW.md)
2. [../main/ARCHITECTURE.md](../main/ARCHITECTURE.md) §8–9
3. [../main/UI_ROUTES.md](../main/UI_ROUTES.md) — `/quotes`
4. [.agents/skills/freelance-document-workflow/SKILL.md](../../.agents/skills/freelance-document-workflow/SKILL.md)
5. [.agents/skills/freelance-monetary-consistency/SKILL.md](../../.agents/skills/freelance-monetary-consistency/SKILL.md)

---

## Paso 0 — Git

```bash
git checkout main && git pull origin main
git checkout -b feature/quotes-fase-5
git push -u origin feature/quotes-fase-5
```

> Si el trabajo lo ejecuta un agente, `commit`/`push`/PR los hace el usuario, no el agente.

---

## Paso 1 — Contratos Zod

Archivo `packages/contracts/src/quotes.ts`:

### Quote

- `id`, `client_id` (int positivo)
- `number` (string no vacía, única por tenant)
- `status` enum: `draft` | `sent` | `accepted` | `rejected` | `converted`
  - `converted` se reserva para Fase 6; en Fase 5 no se expone aún transición de negocio
- `title`, `notes` nullable
- snapshot cliente: `client_name`, `client_email`, `client_tax_id`, `client_address`
- `currency` (snapshot del tenant al emitir)
- `subtotal_cents`, `tax_cents`, `total_cents` (int)
- `tax_rate` (number, default 0 si tenant sin IVA)
- `valid_until` date nullable
- `sent_at`, `accepted_at`, `rejected_at` nullable
- `created_at`, `updated_at`

### QuoteLine

- `id`, `quote_id` (int positivo)
- `description`
- `quantity` decimal positiva con precisión explícita
  - persistencia sugerida: `decimal(12,3)` en BD
  - contrato JSON: número positivo; backend normaliza y valida escala
- `unit_amount_cents`, `tax_rate`, `tax_cents`, `line_total_cents`
- `sort_order` int no negativo

### Inputs

- `QuoteLineInput`
  - `description`, `quantity`, `unit_amount_cents`, `tax_rate?`, `sort_order`
- `QuoteCreate`
  - `client_id`, `title`, `notes`, `valid_until`, `lines`
  - `subtotal_cents`, `tax_cents`, `total_cents` **no** son input confiable
- `QuoteUpdate`
  - permite editar metadata y líneas mientras estado compatible
- `QuoteListResponse`
  - `{ data, meta }`
- `QuoteStatusTransitionResponse`
  - response explícita para `send` / `accept` / `reject`

Agregar también fixtures/ayudas de test para validar que Resource y schema coincidan.

Exportar en `src/index.ts`.

```bash
cd packages/contracts && pnpm exec tsc --noEmit
```

---

## Paso 2 — Migraciones tenant

1. `quotes`
   - FK `client_id` → `clients` con `restrictOnDelete`
   - `number` único por tenant
   - snapshot cliente (`client_name`, `client_email`, `client_tax_id`, `client_address`)
   - `currency`
   - `status`, `valid_until`, `sent_at`, `accepted_at`, `rejected_at`
   - índices sugeridos: `(status)`, `(client_id)`, `(valid_until)`
2. `quote_lines`
   - FK `quote_id` → `quotes` con `cascadeOnDelete`
   - `quantity decimal(12,3)`
   - `sort_order`

**Regla:** no guardar columnas de totales provenientes del frontend sin recalcular en backend.

```bash
cd api && php artisan tenants:migrate
```

---

## Paso 3 — Backend

### Servicios

- `QuoteService` — create/show/update/delete con transacción
- `QuoteLineSynchronizer` — sync de líneas separado si el service empieza a mezclar demasiadas responsabilidades
- `QuoteTotalsCalculator` o método dedicado usando **`MoneyMath`** únicamente para totales
- `QuoteStatusService` — transiciones: `draft→sent`, `sent→accepted|rejected` (validar estados)
- `QuoteNumberGenerator` — numeración por tenant, estable y testeable
- `QuoteSnapshotFactory` o helper puro para congelar datos del cliente al crear cotización

### Reglas backend obligatorias

1. Backend recalcula siempre:
   - `subtotal_cents`
   - `tax_cents`
   - `total_cents`
2. Si `tax_enabled` es `false`:
   - forzar `tax_rate = 0`
   - `tax_cents = 0`
3. No permitir transiciones inválidas:
   - `draft -> accepted` directo: error
   - `accepted -> rejected`: error
   - `rejected -> sent`: error
4. Borrado:
   - se puede borrar solo `draft` en v1, o documentar explícitamente si se permitirá más; no dejar ambiguo
5. Errores de dominio:
   - usar `message` claro y status consistente (`404` / `409` cuando aplique)

### PDF básico

- `QuotePdfGenerator` — Blade/HTML → PDF (dompdf o snappy según dependencia ya usada o añadir una)
- PDF construido desde quote + lines + snapshot persistido
- Ruta: `GET /quotes/{id}/pdf` — auth sanctum, stream download

### HTTP

- Controller `QuoteController`: index, store, show, update, destroy
- Sub-ruta `POST /quotes/{id}/send` (status → sent)
- `POST /quotes/{id}/accept`, `POST /quotes/{id}/reject`
- Requests dedicados para create/update/status actions
- Resource alineado a schema Zod; no devolver campos calculados inconsistentes

### Rutas

`api/routes/tenant.php` bajo `auth:sanctum`.

---

## Paso 4 — Tests API

`tests/Feature/Tenant/QuoteApiTest.php`:

- crear quote con líneas → totales correctos (fixture centavos)
- recálculo backend ignora totales manipulados por frontend
- transición `draft -> sent -> accepted`
- transición inválida devuelve error coherente
- PDF responde 200 y content-type pdf
- tenant sin `tax_enabled` → tax_cents 0
- client snapshot persiste aunque el cliente luego cambie
- borrar cliente con quotes falla por restricción
- numeración se genera y es única
- quote lines respetan `sort_order`

```bash
cd api && php artisan test --filter=QuoteApiTest
```

---

## Paso 5 — Frontend

### Composables

- `useQuotesApi.ts`
- `useQuoteForm.ts`
- `useQuoteLines.ts`
- `hydrateQuoteForm.ts` / `serializeQuotePayload.ts`
- `useQuoteSubmit.ts` si el orquestador crece

**Regla frontend:** UI puede calcular previews para display, pero nunca se asume que esos totales son la fuente de verdad.

### Páginas

- `pages/quotes/index.vue`
- `pages/quotes/new.vue` — selector cliente + líneas dinámicas
- `pages/quotes/[id].vue` — detalle, estados, botón PDF, aceptar/rechazar

### UI / flujo

- Mantener patrón de páginas dedicadas de `UI_ROUTES.md`
- En detalle mostrar:
  - número de cotización
  - snapshot cliente
  - líneas
  - totales
  - estado y timestamps de transición
- Descarga PDF vía `fetch` autenticado + blob; no token en URL

### Menú

Entrada **Cotizaciones** → `/quotes`

```bash
pnpm typecheck:web
```

**Manual:** crear cotización para cliente existente, descargar PDF, pasar a `accepted`, editar cliente y confirmar que la cotización/PDF siguen mostrando snapshot histórico.

---

## Paso 6 — Cierre PR

[WORKFLOW.md § D](./WORKFLOW.md) — título: `feat: quotes module with PDF (phase 5)`

---

## Definition of Done

- [ ] Schemas quotes en contracts
- [ ] Migraciones tenant aplicadas
- [ ] CRUD + cambios de estado + PDF
- [ ] Tests QuoteApiTest verdes
- [ ] UI rutas `/quotes/*` funcionales
- [ ] Totales recalculados solo en backend con `MoneyMath`
- [ ] Snapshot cliente persistido y usado en PDF
- [ ] `number` único por tenant
- [ ] Restricción de borrado de cliente con cotizaciones
- [ ] Descarga PDF autenticada sin token en URL
- [ ] Merge a `main`

---

## No hacer en esta fase

- Conversión a proyecto (Fase 6; solo dejar `status=converted` preparado)
- Finanzas
- Plantillas PDF custom por cliente (Fase 9)
