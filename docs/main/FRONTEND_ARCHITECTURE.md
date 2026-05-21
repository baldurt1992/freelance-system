# Frontend Architecture — Nuxt UI Dashboard

Objetivo: fijar una arquitectura frontend única para `apps/web` y evitar que cada agente invente patrones, APIs de componentes o estructuras de módulo distintas.

---

## 1. Principios

1. Un módulo nuevo debe copiar el patrón del módulo estable más cercano antes de abstraer.
2. La `page` coordina; no concentra columnas, formato, serialización, validaciones visuales y subcomponentes complejos en el mismo archivo.
3. La UI usa Nuxt UI como sistema base; no se inventan componentes ni props sin validar primero en la documentación oficial o en el MCP disponible.
4. Si un patrón ya existe en `clients`, ese patrón es la referencia por defecto hasta documentar otra cosa.
5. Reutilización sí; abstracción genérica solo cuando ya existan al menos 2 módulos coherentes con duplicación real.

---

## 2. Estructura obligatoria por módulo

Ejemplo base:

```txt
apps/web/app/
├── pages/
│   └── quotes/
│       ├── index.vue
│       ├── new.vue
│       └── [id].vue
├── composables/
│   └── quotes/
│       ├── useQuotesApi.ts
│       ├── useQuoteTableColumns.ts
│       ├── useQuoteLines.ts
│       └── quoteFormHelpers.ts
└── components/
    └── quotes/
        ├── QuotesListToolbar.vue
        ├── QuotesDeleteModal.vue
        ├── ui/
        │   ├── QuoteFormFields.vue
        │   └── QuoteLinesEditor.vue
        └── sections/
            └── QuoteSummaryCard.vue
```

### Responsabilidad por archivo

| Pieza | Responsabilidad |
| --- | --- |
| `pages/.../index.vue` | fetch inicial, wiring de acciones, navegación, estado de tabla mínimo |
| `useXApi.ts` | llamadas HTTP puras al backend vía `composables/api/useApi.ts` |
| `useXTableColumns.ts` | definición de columnas, badges, acciones de fila |
| `*FormHelpers.ts` | `hydrate`, `serialize`, normalización y helpers puros |
| `useX.ts` / `useXLines.ts` | estado reutilizable y orquestación local del feature |
| `components/.../ui/*` | inputs y bloques presentacionales sin negocio |
| `components/.../sections/*` | tarjetas o secciones compuestas de una vista detalle |

---

## 3. Reglas para páginas

1. `index.vue` no debe definir inline:
   - columnas grandes de `UTable`,
   - formatters monetarios reutilizables,
   - badges de estado repetibles,
   - modales complejos,
   - formularios largos.
2. `new.vue` y `[id].vue` no deben mezclar en un solo archivo:
   - carga de catálogos,
   - layout completo del formulario,
   - editor de líneas,
   - serialización del payload,
   - validación visual,
   - acciones de submit.
3. Si una `page` supera ~150–200 líneas por mezclar responsabilidades, se extraen componentes o composables antes de seguir creciendo.

---

## 4. Patrón base actual: `clients`

Tomar `clients` como referencia mínima:

- `apps/web/app/pages/clients/index.vue`
- `apps/web/app/composables/clients/useClientsTableColumns.ts`
- `apps/web/app/components/clients/ClientsListToolbar.vue`
- `apps/web/app/components/clients/ClientsDeleteModal.vue`
- `apps/web/app/components/clients/ui/ClientFormFields.vue`

Si un módulo nuevo queda más acoplado que `clients`, no está siguiendo el estándar del repo.

---

## 5. Tablas

### Regla actual

Antes de crear un `tableManager` genérico:

1. alinear el módulo nuevo al patrón de `clients`,
2. extraer columnas a `useXTableColumns.ts`,
3. extraer toolbar/acciones si la tabla las necesita,
4. revisar duplicación real entre al menos 2 módulos ya ordenados.

### Cuándo sí evaluar una abstracción compartida

Solo si se repite de forma estable:

- paginación,
- búsqueda con debounce,
- selección masiva,
- toolbar de tabla,
- acciones estándar de fila,
- estilos `ui` de `UTable`.

La primera abstracción preferida no es un mega `tableManager`, sino piezas pequeñas como:

- `useEntityTableState`
- `useEntityTablePagination`
- `useEntityTableSearch`
- `EntityTableToolbar`

### Coherencia mínima de listas

Si una entidad usa tabla administrativa estilo dashboard, la vista debe intentar igualar la experiencia base de `clients` salvo que exista una razón funcional documentada para no hacerlo.

Capacidades esperadas por defecto:

- filtro global o búsqueda con debounce,
- selección de filas,
- acción masiva cuando el caso de uso existe,
- selector de columnas cuando la densidad de tabla lo justifica,
- paginación consistente,
- estado vacío claro,
- acciones por fila visualmente estables.

Si `clients` ya ofrece estas capacidades y una tabla nueva no, el agente debe justificar la omisión en el plan o en el diff.

---

## 6. Formularios con Nuxt UI

### Reglas obligatorias

1. Usar `UFormField` como wrapper de campo en este proyecto.
2. No usar `UFormGroup` mientras no exista evidencia explícita en la versión instalada y en el patrón del repo.
3. No usar `UInput type="date"` para fechas.
4. Para fechas usar `UInputDate`, según la documentación oficial de Nuxt UI.
5. Antes de introducir un componente nuevo de Nuxt UI, validar nombre, props y patrón de uso en la documentación oficial o en el MCP de Nuxt UI.

### Fecha

Referencia oficial Nuxt UI:

- `UInputDate` en `https://ui.nuxt.com/docs/components/input-date`

La documentación oficial muestra `UInputDate` controlado con `v-model` y `CalendarDate` de `@internationalized/date`. No sustituirlo por un `UInput` nativo con `type="date"` salvo excepción documentada y aprobada.

### Forma recomendada

```txt
UForm
└── QuoteFormFields
    ├── UFormField + USelect / UInput / UTextarea / UInputDate
    └── QuoteLinesEditor
```

### Layout y densidad

1. Los formularios de negocio deben usar contenedores de ancho completo por defecto (`w-full`) y luego limitar lectura con wrappers superiores cuando haga falta (`max-w-*`).
2. Un componente de campos reutilizable (`*FormFields.vue`) debe renderizar sus controles con `w-full`; no debe depender de que la page “lo estire”.
3. Mantener grillas consistentes (`grid-cols-1`, `md:grid-cols-2`) y spacing uniforme entre secciones.
4. Cuando exista un patrón limpio en `clients`, copiar ese layout antes de inventar uno nuevo.

### Accesibilidad obligatoria

1. Todo control de formulario debe tener `id` único y `name` estable.
2. No cerrar un módulo con warnings tipo: `A form field element has neither an id nor a name attribute`.
3. `UFormField` debe enlazar una etiqueta visible con su control; además el control debe exponer `id` y `name`.
4. Si hay errores por campo, el mensaje debe quedar asociado al control correspondiente.
5. Para campos conocidos por navegador (`email`, `tel`, nombre, dirección, fechas relevantes), añadir `autocomplete` cuando aplique.
6. No usar placeholders como sustituto de label.

---

## 7. Diseño y consistencia visual

1. Formularios de negocio deben usar grid consistente (`grid-cols-1`, `md:grid-cols-2`) y spacing uniforme.
2. Los editores de líneas no deben sentirse como una tabla improvisada sin labels; cada bloque debe tener jerarquía visual clara.
3. En cotizaciones orientadas a servicios, la UI debe hablar de **conceptos** o **ítems de la cotización**, no de “líneas”, salvo en detalles puramente técnicos del código.
4. Los totales, estados y acciones primarias deben vivir en zonas visuales predecibles.
5. Si un módulo nuevo se ve sensiblemente peor que `clients`, detenerse y corregir composición antes de seguir agregando features.

---

## 8. Qué no hacer

1. No meter toda la tabla de una entidad dentro de `pages/.../index.vue`.
2. No serializar payloads inline dentro del handler `onSubmit`.
3. No duplicar `formatMoney`, badges de estado o mapeos visuales en varias pages.
4. No usar componentes Nuxt UI “recordados de memoria”.
5. No crear una abstracción genérica transversal solo para tapar un módulo mal estructurado.

---

## 9. Checklist antes de cerrar un módulo frontend

- `page` delgada y orquestadora
- columnas fuera de la page
- formulario extraído a componentes `ui/` o `sections/`
- helpers puros fuera del template principal
- listas coherentes con `clients` cuando el caso de uso es equivalente
- uso correcto de Nuxt UI validado
- sin `UInput type="date"`; usar `UInputDate`
- sin componentes inexistentes o no soportados en la versión instalada
- todos los campos con `id` y `name`
- sin warnings de autofill/accesibilidad en consola
- `nuxi typecheck` limpio

---

## 10. Referencias

- `docs/main/ARCHITECTURE.md`
- `docs/main/ENGINEERING_GUARDRAILS.md`
- `docs/main/UI_ROUTES.md`
- `apps/web/app/pages/clients/index.vue`
- `apps/web/app/components/clients/ui/ClientFormFields.vue`
- Nuxt UI `InputDate`: `https://ui.nuxt.com/docs/components/input-date`
