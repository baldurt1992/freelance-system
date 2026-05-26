# Fase 8 — Cuenta de cobro al completar + email

**Rama:** `feature/billing-fase-8`  
**Prerrequisito:** Fase 7 mergeada en `main`.

---

## Decisiones obligatorias de diseño

1. **Billing document es snapshot histórico inmutable.**
   - Debe persistir datos de cliente, proyecto, moneda y montos al emitir.
   - El PDF no depende del cliente/proyecto vivo para render final.

2. **Numeración y estados deben ser auditables.**
   - `number` único por tenant.
   - `issued_at`, `sent_at`, `paid_at`/equivalente según estado si aplica.

3. **Completar proyecto y emitir billing comparten una sola semántica.**
   - El endpoint `POST /projects/{id}/complete` queda definido aquí como contrato final.
   - Si hubo scaffold previo, debe ajustarse a esta semántica exacta.

4. **No crear ingreso financiero al emitir.**
   - Sigue siendo documento; el ingreso nace en pagos (Fase 7).

---

## Leer primero

1. [../WORKFLOW.md](../WORKFLOW.md)
2. [../../main/FINANCES.md](../../main/FINANCES.md) §4 — billing **no** crea ingreso
3. [../../../.agents/skills/freelance-document-workflow/SKILL.md](../../../.agents/skills/freelance-document-workflow/SKILL.md)
4. [../../main/ENGINEERING_GUARDRAILS.md](../../main/ENGINEERING_GUARDRAILS.md)
5. [../../main/TENANCY.md](../../main/TENANCY.md)
6. [../../main/FRONTEND_ARCHITECTURE.md](../../main/FRONTEND_ARCHITECTURE.md)

---

## Guardrails transversales (obligatorios)

1. Rutas tenant únicamente en `api/routes/tenant.php` bajo `auth:sanctum`.
2. Cambio de shape: primero `packages/contracts`, después Form Request + Resource + composables.
3. Snapshot documental obligatorio y render PDF sin depender de entidades vivas.
4. Frontend: errores en `catch` con `useApiError().toastApiError(...)`; sin toasts genéricos.
5. UI en páginas dedicadas (`/projects/[id]`), usando modal/dialog solo para confirmación breve.

---

## Paso 0 — Git

```bash
git checkout main && git pull origin main
git checkout -b feature/billing-fase-8
git push -u origin feature/billing-fase-8
```

> Si el trabajo lo ejecuta un agente, `commit`/`push`/PR los hace el usuario, no el agente.

---

## Paso 1 — Contratos Zod

`packages/contracts/src/billing.ts`:

- `BillingDocumentSchema`
- status: `draft` | `issued` | `sent` | `paid`
- `id`, `project_id`, `client_id` como enteros positivos
- `number`, `issued_at`, `sent_at` nullable, `pdf_path`
- snapshot cliente/proyecto:
  - `project_name`
  - `client_name`, `client_email`, `client_tax_id`, `client_address`
  - `currency`
- montos espejo del proyecto al emitir

---

## Paso 2 — Migración tenant

Tabla `billing_documents` (+ storage path si aplica).

Campos mínimos:

- FK `project_id` → `projects` con `restrictOnDelete`
- FK `client_id` → `clients` con `restrictOnDelete`
- `number` único por tenant
- snapshot cliente/proyecto
- `pdf_path`, `issued_at`, `sent_at`

```bash
cd api && php artisan tenants:migrate
```

---

## Paso 3 — Completar proyecto → billing

`CompleteProjectService` (transacción):

1. Project `active` → `completed`, `completed_at` = now
2. Crear `BillingDocument` status `issued` con snapshot persistido
3. Generar PDF (misma stack que quotes o template base)
4. Encolar job `SendBillingDocumentEmail` (tenant-aware queue)

**Idempotencia:** segundo complete no duplica document.

Endpoint: `POST /projects/{id}/complete` (contrato definitivo de complete).

---

## Paso 4 — PDF y email

- `BillingPdfGenerator` — datos cliente + proyecto + totales (`MoneyMath`)
- Mailable o notification con adjunto PDF
- Log estructurado: `[Billing] issued`, `project_id`, `billing_document_id`
- El job de email exitoso actualiza status a `sent` y `sent_at`

Config `.env.example`: `MAIL_*` documentado en ONBOARDING (mailtrap dev).

---

## Paso 5 — API lectura

- `GET /projects/{id}/billing-documents`
- `GET /billing-documents/{id}/pdf`

Opcional: `POST /billing-documents/{id}/mark-sent`

Todas en `tenant.php` con `auth:sanctum`.

---

## Paso 6 — Tests

- complete project crea billing document
- PDF endpoint 200
- job dispatched (Queue::fake en test)
- **assert:** complete no crea `finance_entry` income
- complete idempotente no duplica billing
- PDF usa snapshot persistido
- status `sent` solo cambia tras flujo de envío

```bash
cd api && php artisan test --filter=Billing
```

---

## Paso 7 — Frontend

En `pages/projects/[id].vue`:

- Sección **Documentos** — enlace descargar cuenta de cobro PDF
- Botón **Completar proyecto** → confirmación → llama complete endpoint
- Mostrar estado billing (issued/sent)

```bash
pnpm typecheck:web
```

---

## Paso 7.5 — Validación obligatoria

```bash
bash scripts/validate-touched-files.sh <archivos_fase8...>
cd api && php artisan test --filter=Billing
pnpm --filter @freelance/web exec nuxi typecheck
```

---

## Paso 8 — Cierre PR

Título: `feat: billing document on project complete (phase 8)`

---

## Definition of Done

- [x] Complete → PDF + job email
- [x] Sin ingreso Finanzas al emitir (solo al cobrar — ya Fase 7)
- [x] UI muestra documento en detalle proyecto
- [x] Snapshot persistido usado en PDF
- [x] `number` único por tenant
- [x] Tests verdes
- [ ] Merge a `main`

---

## No hacer en esta fase

- Plantillas HTML custom por cliente (Fase 9)
- Portal cliente externo
