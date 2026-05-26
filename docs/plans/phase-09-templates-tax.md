# Fase 9 — Plantillas PDF custom + IVA en UI

**Rama:** `feature/templates-tax-fase-9`  
**Prerrequisito:** Fase 8 mergeada en `main`.

---

## Decisiones obligatorias de diseño

1. **Cambiar IVA no reescribe historia.**
   - El toggle afecta cotizaciones nuevas y, como máximo, cotizaciones en `draft`.
   - Cotizaciones `sent|accepted|rejected|converted`, proyectos con pagos y billing ya emitido permanecen inmutables.

2. **Templates se almacenan en BD tenant en v1.**
   - No dejar ambigua la elección DB vs filesystem en esta fase.
   - Facilita resolución, edición, preview, defaults e integridad multi-tenant.

3. **El renderer PDF recibe variables, no lógica de negocio.**
   - `MoneyMath` calcula antes; la plantilla solo presenta.

4. **HTML de templates es capacidad de owner, no de usuario público.**
   - Preview y update van autenticados por tenant.

---

## Leer primero

1. [WORKFLOW.md](./WORKFLOW.md)
2. [../main/ARCHITECTURE.md](../main/ARCHITECTURE.md) §8
3. [.agents/skills/freelance-monetary-consistency/SKILL.md](../../.agents/skills/freelance-monetary-consistency/SKILL.md)
4. [../main/ENGINEERING_GUARDRAILS.md](../main/ENGINEERING_GUARDRAILS.md)
5. [../main/TENANCY.md](../main/TENANCY.md)
6. [../main/FRONTEND_ARCHITECTURE.md](../main/FRONTEND_ARCHITECTURE.md)

---

## Guardrails transversales (obligatorios)

1. Endpoints de settings/templates en `api/routes/tenant.php` bajo `auth:sanctum`.
2. Contratos primero en `packages/contracts`; luego Request/Resource y composables.
3. `MoneyMath` sigue siendo el único motor de cálculo; templates no ejecutan lógica fiscal.
4. Frontend: manejo de errores con `useApiError().toastApiError(...)` y 422 por campo en formularios.
5. Si se agregan formularios de settings/templates: `UFormField`, campos con `id` y `name`, y `UInputDate` cuando aplique fecha.

---

## Paso 0 — Git

- [x] Rama `feature/templates-tax-fase-9` creada y pusheada
- [ ] Merge a `main` (usuario)

```bash
git checkout main && git pull origin main
git checkout -b feature/templates-tax-fase-9
git push -u origin feature/templates-tax-fase-9
```

> Si el trabajo lo ejecuta un agente, `commit`/`push`/PR los hace el usuario, no el agente.

---

## Paso 1 — Tenant settings IVA

- [x] `tax_enabled`, `currency` y `tax_rate` en tenant `data` (`Tenant` + `tenant:provision --tax-enabled`)
- [x] `GET /settings` y `PATCH /settings` en `tenant.php` (`SettingsController`, `TenantSettingsService`)
- [x] Body `{ tax_enabled: boolean }` — contrato `UpdateTenantSettingsSchema`
- [x] Al activar: quotes nuevas con IVA; `DraftQuoteTaxRecalculator` recalcula solo `draft`
- [x] Al desactivar: `tax_cents = 0` vía `QuoteTotalsCalculator` + recálculo de drafts; históricos inmutables
- [x] Tests de draft on/off e inmutabilidad en `TemplateTaxTest`

---

## Paso 2 — Plantillas documentos

- [x] Migración `document_templates` + seed defaults (`quote.html`, `billing.html`)
- [x] Modelo `DocumentTemplate`
- [x] `TemplateResolver::resolve(type, client_id)` — cliente específico → default tenant
- [x] `QuotePdfGenerator` y `BillingPdfGenerator` delegan a `TemplateRenderer` + `TemplateVariableBuilder` + `PdfRenderer`
- [x] Totales formateados en PHP (`MoneyFormatter`); sin math fiscal en HTML

### Modelo / storage

- `document_templates` tenant:
  - `id` int positivo
  - `type` (`quote` | `billing`)
  - `client_id` nullable
  - `name`
  - `html_body`
  - `is_default`
  - timestamps
- Almacenamiento oficial v1: **base de datos tenant**

### Resolución

`TemplateResolver::resolve(type, client_id)` → template más específico (client) o default.

### Generadores PDF

Refactor `QuotePdfGenerator` y `BillingPdfGenerator` para inyectar HTML resuelto + variables (cliente, líneas, totales formateados desde PHP, no Blade math).

---

## Paso 3 — API plantillas (mínimo v1)

- [x] `GET /document-templates?type=quote|billing` — `DocumentTemplateController@index`
- [x] `PUT /document-templates/{id}` — actualizar `html_body` (owner v1, auth sanctum)
- [x] `POST /document-templates/preview` — PDF con sample data o `html_body` enviado
- [x] Un solo `is_default` por `type` + `client_id` — `DocumentTemplateService::update`
- [x] Rutas en `tenant.php` bajo `auth:sanctum`

---

## Paso 4 — Frontend settings

- [x] `pages/settings/billing.vue` — toggle IVA + aviso históricos (`useSettingsApi`, `toastApiError`)
- [x] `pages/settings/templates.vue` — textarea HTML + preview + guardar (`useDocumentTemplatesApi`)
- [x] Nav en `pages/settings.vue` → Facturación / Plantillas
- [x] `pnpm typecheck:web` en verde

---

## Paso 4.5 — Validación obligatoria

- [x] `bash scripts/validate-touched-files.sh` — PHP `php -l` OK en archivos fase 9
- [x] `nuxi typecheck` — verde en entorno validado

```bash
bash scripts/validate-touched-files.sh <archivos_fase9...>
pnpm --filter @freelance/web exec nuxi typecheck
```

---

## Paso 5 — Tests

- [x] `tax_enabled` false → `tax_cents` 0 en quote nueva
- [x] `tax_enabled` true → cálculo MoneyMath (19% sobre subtotal)
- [x] template por cliente usado en render quote
- [x] toggle IVA no altera quote `accepted` ni billing emitido
- [x] fallback template default (`Cotización predeterminada`)
- [x] list/update/preview API plantillas

```bash
cd api && bash ../scripts/test-tenant-safe.sh tests/Feature/Tenant/TemplateTaxTest.php
cd api && php artisan test
```

**Evidencia (2026-05-25):** `TemplateTaxTest` — 12 passed, 47 assertions, ~3.9s (`php artisan test --filter=TemplateTax`). `pnpm --filter @freelance/web exec nuxi typecheck` también verde.

---

## Paso 6 — Cierre PR

- [ ] Commit de archivos nuevos + modificados (usuario)
- [ ] PR con título abajo
- [ ] Merge a `main` (usuario)

Título: `feat: document templates and tax toggle (phase 9)`

---

## Entregables (implementado en rama)

| Área | Archivos clave |
| --- | --- |
| Contratos | `packages/contracts/src/settings.ts`, `document-templates.ts` |
| Settings API | `SettingsController`, `TenantSettingsService`, `DraftQuoteTaxRecalculator`, `TenantSettingsResource` |
| Templates API | `DocumentTemplateController`, `DocumentTemplateService`, `TemplateResolver`, `TemplateRenderer`, `TemplateVariableBuilder`, `PdfRenderer` |
| PDF | `QuotePdfGenerator`, `BillingPdfGenerator` (refactor) |
| BD tenant | `2026_05_26_100000_create_document_templates_table.php`, `api/resources/templates/defaults/*.html` |
| Tests | `api/tests/Feature/Tenant/TemplateTaxTest.php`, `TenantTestCase` (shared tenant) |
| Frontend | `pages/settings/billing.vue`, `pages/settings/templates.vue`, `composables/settings/*` |
| Tooling | `scripts/test-tenant-safe.sh`, `scripts/kill-runaway-php-tests.sh`, `docs/main/WSL_TESTING.md` |

---

## Definition of Done

- [x] Toggle IVA en settings funcional
- [x] PDF quote/billing usan template resoluble
- [x] Editor mínimo de plantilla
- [x] Tests verdes
- [ ] Merge a `main`

---

## Después de Fase 9

Pulido UI global (skills `modern-web-guidance`, `frontend-design`) — **fuera** de este plan; rama aparte `feature/ui-polish` si aplica.

---

## No hacer en esta fase

- Refactor completo de todas las pantallas
- Multi-idioma plantillas
