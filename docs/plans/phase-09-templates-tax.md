# Fase 9 — Plantillas PDF custom + IVA en UI

**Rama:** `feature/templates-tax-fase-9`  
**Prerrequisito:** Fase 8 mergeada en `main`.

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

---

## Paso 1 — Tenant settings IVA

1. Asegurar `tax_enabled` y `currency` en tenant `data` (ya existe provision).
2. Endpoint: `PATCH /settings` o `PATCH /tenant/settings` (tenant auth):
   - body `{ tax_enabled: boolean }`
3. Al activar IVA: quotes/projects recalculan con `MoneyMath` y `tax_rate` > 0 según reglas.
4. Al desactivar: forzar `tax_rate = 0` en cálculos (no confiar en totales guardados viejos sin recalcular).

Tests: quote con tax on/off.

---

## Paso 2 — Plantillas documentos

### Modelo / storage

- `document_templates` tenant: `type` (`quote` | `billing`), `client_id` nullable (null = default tenant), `html_body`, `is_default`
- o archivos en `storage/app/tenant-{id}/templates/` si prefieres filesystem — documentar elección en PR

### Resolución

`TemplateResolver::resolve(type, client_id)` → template más específico (client) o default.

### Generadores PDF

Refactor `QuotePdfGenerator` y `BillingPdfGenerator` para inyectar HTML resuelto + variables (cliente, líneas, totales formateados desde PHP, no Blade math).

---

## Paso 3 — API plantillas (mínimo v1)

- `GET /document-templates?type=quote`
- `PUT /document-templates/{id}` — actualizar HTML (owner only v1)
- Preview: `POST /document-templates/preview` con sample data

---

## Paso 4 — Frontend settings

En `pages/settings/index.vue` (o nueva `settings/billing.vue`):

- Toggle **Activar IVA** → llama PATCH settings
- Aviso cuando cambia (recalcular cotizaciones abiertas — mensaje UX simple)

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
