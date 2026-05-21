# UI — rutas, vistas completas y diseño

**Principio:** las entidades de negocio se **detallan y editan en páginas dedicadas**, no en modales como pantalla principal. Los modales quedan para confirmaciones breves o acciones destructivas puntuales.

**Stack UI:** Nuxt 4 + Nuxt UI Dashboard (`apps/web`), `ssr: false`.

---

## 1. Cuándo usar qué

| Patrón                               | Uso                                                            | Ejemplo                                      |
| ------------------------------------ | -------------------------------------------------------------- | -------------------------------------------- |
| **Vista completa** (`/recurso/[id]`) | Detalle rico, varias secciones, acciones frecuentes            | Proyecto “Cali” → `/projects/{id}`           |
| **Vista lista** (`/recurso`)         | Tabla/cards + filtros; clic abre detalle                       | `/projects`, `/clients`                      |
| **Vista crear/editar**               | Formularios con espacio (`/recurso/new`, `/recurso/[id]/edit`) | Nueva cotización                             |
| **`<dialog>` / confirmación nativa** | Sí/no, riesgo, poco texto                                      | “Marcar como pagado”                         |
| **Modal Nuxt UI**                    | Solo si no cabe en página y es acción secundaria               | Borrar cliente (v1 puede ser página también) |

**Evitar:** CRUD de proyecto, cliente o finanzas dentro de `UModal` a pantalla completa simulada.

Antes de implementar UI nueva, consultar skills:

- `modern-web-guidance` — layout, forms, dialogs, performance, a11y (ejecutar `search` al inicio del feature).
- `frontend-design` — dirección visual, tipografía, densidad.
- `accessibility` — contraste, teclado, etiquetas.
- `vue-best-practices` / `nuxt` — Composition API, rutas, composables.

---

## 2. Rutas previstas (Nuxt `app/pages/`)

| Ruta                    | Vista                        | Notas                                     |
| ----------------------- | ---------------------------- | ----------------------------------------- |
| `/`                     | Dashboard                    | Resumen operativo (home actual)           |
| `/login`                | Auth                         | Layout `auth`                             |
| `/clients`              | Lista clientes               | Reemplaza demo `customers`                |
| `/clients/[id]`         | Detalle cliente              | Datos + cotizaciones/proyectos vinculados |
| `/clients/new`          | Alta cliente                 | Formulario página completa                |
| `/quotes`               | Lista cotizaciones           |                                           |
| `/quotes/[id]`          | Detalle cotización           | PDF, estados, convertir a proyecto        |
| `/quotes/new`           | Nueva cotización             |                                           |
| `/projects`             | Lista proyectos              | Badge por cobrar                          |
| `/projects/[id]`        | **Detalle proyecto**         | Ver §3 — corazón del flujo de cobros      |
| `/finances`             | Finanzas                     | Tabs: Resumen / Ingresos / Gastos         |
| `/finances/entries/new` | Nuevo ingreso o gasto manual | Página (no modal)                         |
| `/settings/*`           | Config tenant                | Ya en template                            |

Parámetro `[id]` = identificador del recurso según el dominio vigente. Hoy en tenant predomina `int` positivo; no asumir `uuid`.

---

## 3. Detalle de proyecto — `/projects/[id]`

Página **única** con layout de dashboard (`UDashboardPanel`), no slideover ni modal.

### Estructura visual (desktop)

```txt
┌─ Header ─────────────────────────────────────────────────┐
│ ← Proyectos    Sitio web Cali    [Activo] [Por cobrar]   │
│ Cliente: ACME S.A.S. · Tipo: freelance                 │
└────────────────────────────────────────────────────────┘

┌─ Columna principal (~2/3) ─────┐ ┌─ Columna lateral ────┐
│ Resumen                         │ Cobros (sticky)       │
│ · fechas, notas, origen quote   │ Total / Cobrado /     │
│                                 │ Por cobrar            │
│ Documentos                      │ input + botones       │
│ · cotización · cuenta de cobro  │ historial pagos       │
│                                 │                       │
│ Timeline / actividad (futuro)   │ Acciones              │
│                                 │ · Completar proyecto  │
└─────────────────────────────────┘ └───────────────────────┘
```

En **móvil**: mismas secciones en **una columna** (Cobros después del header, bien visible — no escondido en tab).

### Sección Cobros

Implementación según [PROJECTS.md](./PROJECTS.md): pago parcial + marcar como pagado. Controles inline en la página; confirmación de cierre con `<dialog>` nativo o `UModal` **pequeño** (solo copy + Confirmar/Cancelar).

### Navegación

- Lista → clic fila → `navigateTo('/projects/' + id)`
- Breadcrumb: `Proyectos / {nombre}`

---

## 4. Estructura de componentes (Fase 6+)

```txt
apps/web/app/
├── pages/
│   ├── projects/
│   │   ├── index.vue          # lista
│   │   └── [id].vue           # detalle completo
│   └── finances/
│       ├── index.vue
│       └── entries/new.vue
└── components/
    └── Projects/
        ├── ui/                # badges, money rows
        ├── sections/
        │   ├── ProjectHeader.vue
        │   ├── ProjectSummary.vue
        │   ├── ProjectPaymentsCard.vue
        │   └── ProjectDocuments.vue
        └── forms/             # si edit inline en página
```

Lógica en `composables/projects/*`, no en la page > ~150 líneas.

---

## 5. Diseño y calidad

1. **Nuxt UI** como sistema base; personalizar con `app.config.ts` y tokens en `assets/css/main.css`.
2. **Dinero:** siempre `formatMoney()`; números grandes legibles en cards de cobro.
3. **Estados:** badges con color semántico (`success` pagado, `warning` por cobrar).
4. **modern-web-guidance:** preferir `<dialog>` para confirmaciones; forms con labels explícitos; evitar scroll lock innecesario en páginas completas.
5. **Rendimiento:** listas largas con paginación; detalle carga `GET /projects/{id}` + payments en paralelo si hace falta.

---

## 6. Referencias

- [PROJECTS.md](./PROJECTS.md) — cobros en detalle
- [FINANCES.md](./FINANCES.md) — módulo Finanzas
- [ARCHITECTURE.md](./ARCHITECTURE.md) §6
- `.agents/skills/modern-web-guidance/SKILL.md`
