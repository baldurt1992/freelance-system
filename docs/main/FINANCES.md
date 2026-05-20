# Finanzas — movimientos, balance mensual e ingresos/egresos

**Nombre en producto (UI):** Finanzas  
**Nombre técnico (API/código):** `finances` — tabla `finance_entries`  
**Concepto interno:** libro de movimientos (cash-basis); el usuario **no** ve el término “libro”.

---

## 1. Para qué sirve

Vista unificada del dinero del tenant en el **mes calendario**:

- **Ingresos** — por cobro de proyectos y **manuales** (ej. premio de rifa, regalo, ingreso extra no ligado a un proyecto).
- **Egresos (gastos)** — CRUD simple (suscripciones, AI tooling, etc.).
- **Resumen / balance del mes** — ingresos − gastos = neto (sobrante, faltante o equilibrio).

**No reemplaza:** cotizaciones, pagos operativos del proyecto ni cuenta de cobro (PDF/email). Es el reflejo contable de “entró o salió dinero” con fecha.

---

## 2. Estructura en la app

Vistas de página completa ([UI_ROUTES.md](./UI_ROUTES.md)), no modales como pantalla principal.

```txt
/finances (menú)
├── Resumen      → balance del mes (totales + neto)
├── Ingresos     → listado + /finances/entries/new para manual
└── Gastos       → listado + alta en página
```

Rutas API sugeridas (tenant, prefijo `/api/v1`):

| Método    | Ruta                              | Uso                                 |
| --------- | --------------------------------- | ----------------------------------- |
| GET       | `/finances/summary?month=YYYY-MM` | Totales y neto del mes              |
| GET       | `/finances/entries`               | Listado filtrable (`type`, `month`) |
| POST      | `/finances/entries`               | Crear ingreso o gasto manual        |
| GET       | `/finances/entries/{id}`          | Detalle                             |
| PUT/PATCH | `/finances/entries/{id}`          | Editar manual                       |
| DELETE    | `/finances/entries/{id}`          | Borrar solo manuales                |

---

## 3. Modelo de datos (`finance_entries`)

| Campo          | Tipo                  | Notas                                                                                 |
| -------------- | --------------------- | ------------------------------------------------------------------------------------- |
| `id`           | uuid                  |                                                                                       |
| `type`         | `income` \| `expense` |                                                                                       |
| `amount_cents` | int                   | Siempre positivo; el signo lo da `type`                                               |
| `occurred_on`  | date                  | Fecha contable → agrupa por mes                                                       |
| `description`  | string                | Obligatorio en manuales; opcional en automáticos                                      |
| `category`     | string/enum           | Gastos: `subscriptions`, `ai_tools`, … Ingresos manuales: `prize`, `gift`, `other`, … |
| `source_type`  | string nullable       | Ver §4                                                                                |
| `source_id`    | uuid nullable         | Enlace al origen si aplica                                                            |
| `is_manual`    | bool                  | `true` = creado desde Finanzas; editable/borrable                                     |
| `created_at`   | datetime              |                                                                                       |

Índices: `(occurred_on)`, `(type, occurred_on)`, `(source_type, source_id)` único donde aplique idempotencia.

---

## 4. Origen de los movimientos (`source_type`)

| `source_type`      | `type`           | Cuándo se crea                                                    | `is_manual` |
| ------------------ | ---------------- | ----------------------------------------------------------------- | ----------- |
| `project_payment`  | income           | Al registrar un **pago** de proyecto (fecha = `paid_at` del pago) | false       |
| `manual`           | income / expense | Usuario crea desde Finanzas                                       | true        |
| `billing_document` | —                | **No** genera ingreso; solo documento al completar proyecto       | —           |

### Ingresos de proyecto (automáticos)

1. Completar proyecto → cuenta de cobro (issued/sent). **Sin** entrada en Finanzas aún.
2. Cada **pago parcial** (`POST .../payments`) → entrada `income` por ese monto.
3. **Marcar como pagado** (`POST .../mark-paid`) → una entrada por **solo** `balance_due_cents` (total completo si no hubo parciales; restante si ya había cobros). Ver [PROJECTS.md](./PROJECTS.md).
4. Idempotencia: un pago / un cierre no debe duplicar entrada.

### Ingresos manuales (obligatorio v1)

Casos: rifa, premio, regalo, freelance no cargado en el sistema, reembolso personal, etc.

- `type=income`, `is_manual=true`, `source_type=manual`, `source_id=null`
- Campos UI: monto, fecha, descripción (ej. “Rifa empresa — 2.000.000 COP”), categoría opcional
- Entran al **mismo** resumen mensual que los ingresos por proyecto

### Gastos (siempre manuales en v1)

- `type=expense`, `is_manual=true`, `source_type=manual`
- CRUD completo desde pestaña **Gastos**

---

## 5. Balance del mes

Para `month=YYYY-MM`:

```txt
total_income_cents  = SUM(entries WHERE type=income  AND occurred_on en mes)
total_expense_cents = SUM(entries WHERE type=expense AND occurred_on en mes)
net_cents           = total_income_cents - total_expense_cents
```

| `net_cents` | Etiqueta UI |
| ----------- | ----------- |
| > 0         | Sobrante    |
| < 0         | Faltante    |
| = 0         | Equilibrio  |

Moneda: `tenant.settings.currency` (ej. COP). Formato solo en UI con `formatMoney()`.

---

## 6. Reglas de negocio

1. **No** crear ingreso al emitir/enviar cuenta de cobro; solo al **cobro** (pago registrado) o ingreso **manual**.
2. Entradas automáticas (`is_manual=false`): no editar monto; anular vía reversión del pago o regla explícita (fase posterior).
3. Entradas manuales: editar y eliminar permitido.
4. Montos en centavos; cálculos con `MoneyMath`.
5. Ingresos ligados a proyecto deben tener `source_type` + `source_id`; ingresos manuales usan `source_type=manual` sin enlace obligatorio.

---

## 7. Contratos (`packages/contracts`)

Archivo: `src/finances.ts` (schemas `FinanceEntry`, `FinanceSummary`, filtros de listado).

---

## 8. Referencias

- [ARCHITECTURE.md](./ARCHITECTURE.md) §1 y §9
- [.agents/skills/freelance-document-workflow/SKILL.md](../../.agents/skills/freelance-document-workflow/SKILL.md)
- [bootstrap.md](../plans/bootstrap.md) — Fase 7
