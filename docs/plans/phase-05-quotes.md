# Fase 5 — Cotizaciones + PDF básico

**Rama:** `feature/quotes-fase-5`  
**Prerrequisito:** Fase 4 mergeada en `main` (`clients` + `packages/contracts`).  
**Recomendado antes o al inicio:** [Fase 4.5 — Manejo de errores](./phase-04.5-error-handling.md) (evita repetir toasts genéricos en cotizaciones).

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

---

## Paso 1 — Contratos Zod

Archivo `packages/contracts/src/quotes.ts`:

### Quote

- `id`, `client_id` (uuid), `status` enum: `draft` | `sent` | `accepted` | `rejected` | `converted`
- `title`, `notes` nullable
- `subtotal_cents`, `tax_cents`, `total_cents` (int)
- `tax_rate` (number, default 0 si tenant sin IVA)
- `valid_until` date nullable
- `created_at`, `updated_at`

### QuoteLine

- `id`, `quote_id`
- `description`, `quantity` (number > 0), `unit_amount_cents`, `tax_rate`, `tax_cents`, `line_total_cents`

Schemas: `QuoteCreate`, `QuoteUpdate`, `QuoteLineInput`, list response.

Exportar en `src/index.ts`.

```bash
cd packages/contracts && pnpm exec tsc --noEmit
```

---

## Paso 2 — Migraciones tenant

1. `quotes` — FK `client_id` → `clients`
2. `quote_lines` — FK `quote_id` → `quotes`, cascade delete lines

```bash
cd api && php artisan tenants:migrate
```

---

## Paso 3 — Backend

### Servicios

- `QuoteService` — CRUD quote + sync lines en transacción
- `QuoteTotalsCalculator` o método en Service usando **`MoneyMath`** únicamente para totales
- `QuoteStatusService` — transiciones: `draft→sent`, `sent→accepted|rejected` (validar estados)

### PDF básico

- `QuotePdfGenerator` — Blade/HTML → PDF (dompdf o snappy según dependencia ya usada o añadir una)
- Ruta: `GET /quotes/{id}/pdf` — auth sanctum, stream download

### HTTP

- Controller `QuoteController`: index, store, show, update, destroy
- Sub-ruta `POST /quotes/{id}/send` (status → sent) — opcional v1 si envío es solo cambio estado
- `POST /quotes/{id}/accept`, `POST /quotes/{id}/reject`

### Rutas

`api/routes/tenant.php` bajo `auth:sanctum`.

---

## Paso 4 — Tests API

`tests/Feature/Tenant/QuoteApiTest.php`:

- crear quote con líneas → totales correctos (fixture centavos)
- transición accepted
- PDF responde 200 y content-type pdf
- tenant sin `tax_enabled` → tax_cents 0

```bash
cd api && php artisan test --filter=QuoteApiTest
```

---

## Paso 5 — Frontend

### Composables

- `useQuotesApi.ts`, `useQuoteForm.ts`, `useQuoteLines.ts`

### Páginas

- `pages/quotes/index.vue`
- `pages/quotes/new.vue` — selector cliente + líneas dinámicas
- `pages/quotes/[id].vue` — detalle, estados, botón PDF (link API con token o fetch blob), aceptar/rechazar

### Menú

Entrada **Cotizaciones** → `/quotes`

```bash
pnpm typecheck:web
```

**Manual:** crear cotización para cliente existente, descargar PDF, pasar a `accepted`.

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
- [ ] Merge a `main`

---

## No hacer en esta fase

- Conversión a proyecto (Fase 6)
- Finanzas
- Plantillas PDF custom por cliente (Fase 9)
