# Proyectos — cobros y pagos parciales (UX)

Los cobros viven en el **proyecto**, no en Finanzas. Finanzas refleja el ingreso al registrar un pago o al **marcar como pagado** ([FINANCES.md](./FINANCES.md)).

**Presentación:** detalle en **vista completa** [`/projects/[id]`](./UI_ROUTES.md) — no modal. Ej.: proyecto “Cali” → `/projects/{uuid}` con secciones y espacio para cobros, documentos y resumen.

---

## Dos caminos a **Pagado totalmente**

| Camino                 | Cuándo                                                   | Qué pasa                                                               |
| ---------------------- | -------------------------------------------------------- | ---------------------------------------------------------------------- |
| **Pagos parciales**    | Cobros de a poco (monto en el input)                     | Cada clic suma a Cobrado; cuando Por cobrar = 0 → pagado               |
| **Marcar como pagado** | Cobró todo de una vez o quiere cerrar sin ir pago a pago | Un clic: estado **Pagado totalmente** + ingreso por lo que **faltaba** |

`Pagado totalmente` = `balance_due_cents === 0`.

### Marcar como pagado (regla de negocio)

Botón **Marcar como pagado** en la sección **Cobros** de la página (sin escribir monto).

`amount_to_book = balance_due_cents`

| Situación             | Ingreso en Finanzas |
| --------------------- | ------------------- |
| Sin parciales previos | Total acordado      |
| Ya hubo parciales     | Solo el restante    |
| Ya en cero            | Idempotente         |

Confirmación: `<dialog>` o modal **pequeño** (solo texto + Confirmar) — no una pantalla aparte.

---

## UI — sección Cobros (en `/projects/[id]`)

Parte de la página de detalle; layout en [UI_ROUTES.md](./UI_ROUTES.md).

```txt
Cobros
  Total / Cobrado / Por cobrar
  [ monto ]  [ Registrar pago parcial ]
  [ Marcar como pagado ]
  Historial de pagos (lista en página, no modal)
```

Cuando pagado: badge **Pagado totalmente**; ocultar controles.

### Lista `/projects`

Tabla o cards → clic → detalle. Columna/badge **Por cobrar** / **Pagado totalmente**.

---

## Qué NO hacer en v1

| Anti-patrón                             | Motivo                       |
| --------------------------------------- | ---------------------------- |
| Detalle de proyecto en `UModal` grande  | Sin espacio; mal UX acordado |
| Solo parciales sin “Marcar como pagado” | No cubre pago único          |
| CRUD de pagos en menú aparte            | Acción vive en el proyecto   |

---

## API (Fase 6)

| Método | Ruta                       | Uso                         |
| ------ | -------------------------- | --------------------------- |
| GET    | `/projects/{id}`           | Detalle para la página      |
| POST   | `/projects/{id}/payments`  | Pago parcial                |
| POST   | `/projects/{id}/mark-paid` | Cierre + ingreso pendiente  |
| GET    | `/projects/{id}/payments`  | Historial en sección Cobros |

---

## Referencias

- [UI_ROUTES.md](./UI_ROUTES.md)
- [FINANCES.md](./FINANCES.md) §4
- [ARCHITECTURE.md](./ARCHITECTURE.md) §6
- [.agents/skills/freelance-document-workflow/SKILL.md](../../.agents/skills/freelance-document-workflow/SKILL.md)
