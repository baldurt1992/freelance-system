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

---

## Paso 0 — Git

```bash
git checkout main && git pull origin main
git checkout -b feature/templates-tax-fase-9
git push -u origin feature/templates-tax-fase-9
```

> Si el trabajo lo ejecuta un agente, `commit`/`push`/PR los hace el usuario, no el agente.

---

## Paso 1 — Tenant settings IVA

1. Asegurar `tax_enabled` y `currency` en tenant `data` (ya existe provision).
2. Endpoint: `PATCH /settings` o `PATCH /tenant/settings` (tenant auth):
   - body `{ tax_enabled: boolean }`
3. Al activar IVA:
   - afecta quotes nuevas
   - puede recalcular solo quotes en `draft`
4. Al desactivar:
   - forzar `tax_rate = 0` para quotes nuevas y `draft`
   - **no** reescribir documentos históricos ya emitidos/aceptados/convertidos/completados

Tests: quote `draft` con tax on/off + assert de inmutabilidad histórica.

---

## Paso 2 — Plantillas documentos

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

- `GET /document-templates?type=quote`
- `PUT /document-templates/{id}` — actualizar HTML (owner only v1)
- Preview: `POST /document-templates/preview` con sample data
- Validar que exista solo un default activo por `type` en v1

---

## Paso 4 — Frontend settings

En `pages/settings/index.vue` (o nueva `settings/billing.vue`):

- Toggle **Activar IVA** → llama PATCH settings
- Aviso cuando cambia:
  - afecta nuevas cotizaciones
  - puede recalcular drafts
  - no modifica documentos históricos

Editor plantilla (v1 simple):

- `pages/settings/templates.vue` — textarea HTML + preview link
- Sin WYSIWYG pesado en v1

```bash
pnpm typecheck:web
```

---

## Paso 5 — Tests

- tax_enabled false → tax_cents 0 en quote nueva
- tax_enabled true → cálculo con MoneyMath coherente
- template por cliente usado en PDF quote
- toggle IVA no altera quote histórica aceptada / billing emitido
- fallback template default cuando no hay template por cliente

```bash
cd api && php artisan test
```

---

## Paso 6 — Cierre PR

Título: `feat: document templates and tax toggle (phase 9)`

---

## Definition of Done

- [ ] Toggle IVA en settings funcional
- [ ] PDF quote/billing usan template resoluble
- [ ] Editor mínimo de plantilla
- [ ] Tests verdes
- [ ] Merge a `main`

---

## Después de Fase 9

Pulido UI global (skills `modern-web-guidance`, `frontend-design`) — **fuera** de este plan; rama aparte `feature/ui-polish` si aplica.

---

## No hacer en esta fase

- Refactor completo de todas las pantallas
- Multi-idioma plantillas
