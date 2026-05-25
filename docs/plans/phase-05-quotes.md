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
- `pages/quotes/new.vue` — selector cliente + conceptos dinámicos
- `pages/quotes/[id].vue` — detalle, estados, botón PDF, aceptar/rechazar

### UI / flujo

- Mantener patrón de páginas dedicadas de `UI_ROUTES.md`
- En detalle mostrar:
  - número de cotización
  - snapshot cliente
  - conceptos de la cotización
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

## Correcciones pendientes obligatorias antes de cerrar la fase

Si ya existe una implementación parcial de Fase 5, **no marcarla como terminada** hasta completar este bloque.

### 1. Unificar semántica de `valid_until`

Actualmente no se debe mezclar `date` y `datetime`.

- Decisión cerrada: `valid_until` es **date**, no datetime.
- Ajustar en:
  - `packages/contracts/src/quotes.ts`
  - `QuoteResource`
  - Form Requests
  - helpers frontend de serialize/hydrate
- Aceptación:
  - backend responde `YYYY-MM-DD`
  - contrato Zod acepta date string coherente
  - frontend no convierte innecesariamente a ISO datetime

### 2. Arreglar descarga PDF autenticada en frontend

Si el hub HTTP fuerza `responseType: "json"`, la descarga PDF queda rota aunque el endpoint backend responda bien.

- Ajustar `useApi` para respetar `responseType` recibido (`blob`, `json`, etc.).
- Verificar que `useQuotesApi.downloadPdf()` realmente descargue un `Blob`.
- Aceptación:
  - click en PDF desde `/quotes/[id]` descarga archivo válido
  - sin token en URL

### 3. Rehacer `QuoteNumberGenerator`

No se acepta numeración basada en `count() + 1`.

Riesgos de ese enfoque:

- colisión por concurrencia,
- reutilización de números tras borrar borradores,
- pérdida de trazabilidad documental.

Implementar numeración monotónica estable por tenant. Opciones válidas:

- secuencia persistida por tenant,
- tabla de counters/document_sequences,
- estrategia equivalente testeable e idempotente.

Aceptación:

- dos creates concurrentes no chocan,
- borrar un draft no reutiliza número,
- `number` sigue siendo único por tenant.

### 4. Cerrar semántica de `tax_rate` a nivel quote

Hoy hay riesgo de inconsistencia si las líneas aceptan `tax_rate` individual pero el quote guarda un `tax_rate` agregado ambiguo.

Elegir **una** estrategia y documentarla en código/contrato:

- **Opción A — tasa única por quote**
  - todas las líneas usan la misma tasa efectiva,
  - `quote.tax_rate` sí tiene sentido,
  - backend fuerza consistencia.

- **Opción B — tasa por línea**
  - `quote.tax_rate` se elimina del contrato/resource/modelo,
  - la verdad fiscal queda en las líneas,
  - los agregados del quote son solo `subtotal_cents`, `tax_cents`, `total_cents`.

Para v1, si no hay una necesidad real de múltiples tasas, preferir **Opción A** por simplicidad.

### 5. Revalidación mínima obligatoria tras corregir

Ejecutar y reportar:

```bash
bash scripts/validate-touched-files.sh <archivos_quotes...>
cd api && php artisan test --filter=QuoteApiTest
pnpm --filter @freelance/web exec nuxi typecheck
```

Y además prueba manual:

1. crear quote,
2. descargar PDF desde UI,
3. verificar `valid_until`,
4. confirmar numeración creciente sin reutilización,
5. verificar consistencia de IVA/tax según decisión final.

---

## Paso 6 — Cierre PR

[WORKFLOW.md § D](./WORKFLOW.md) — título: `feat: quotes module with PDF (phase 5)`

---

## Definition of Done

- [x] Schemas quotes en contracts
- [x] Migraciones tenant aplicadas
- [x] CRUD + cambios de estado + PDF
- [x] Tests QuoteApiTest verdes
- [x] UI rutas `/quotes/*` funcionales
- [x] Totales recalculados solo en backend con `MoneyMath`
- [x] Snapshot cliente persistido y usado en PDF
- [x] `number` único por tenant
- [x] Restricción de borrado de cliente con cotizaciones
- [x] Descarga PDF autenticada sin token en URL
- [x] Merge a `main`

---

## No hacer en esta fase

- Conversión a proyecto (Fase 6; solo dejar `status=converted` preparado)
- Finanzas
- Plantillas PDF custom por cliente (Fase 9)

---

## Extensión UX — Editor de conceptos sin regresiones

Esta extensión aplica sobre la implementación existente de Fase 5.  
No cambia el modelo de datos ni los contratos backend. Solo redefine la **experiencia de edición de conceptos** en frontend para evitar scroll excesivo, lenguaje heredado de ERP y comportamientos inconsistentes de inputs.

### Objetivo

Reemplazar el editor largo de conceptos abiertos por defecto por una experiencia más escalable para cotizaciones de servicios en LATAM, manteniendo:

- contratos `QuoteLineInput` / `QuoteLine`,
- persistencia en `quote_lines`,
- cálculo backend en centavos,
- serialización estable hacia `unit_amount_cents`,
- accesibilidad y coherencia con `docs/main/FRONTEND_ARCHITECTURE.md`.

### Regla de producto cerrada

En UI de negocio, el término visible es **conceptos de la cotización**.  
`lines` / `quote_lines` se reservan para detalles técnicos internos, nombres de tabla y shapes existentes.

### Dependencia obligatoria: Nuxt UI MCP / docs oficiales

Antes de implementar cualquier cambio de componente:

1. Validar en el MCP de Nuxt UI o en la documentación oficial el componente exacto.
2. No introducir wrappers o controladores manuales si Nuxt UI ya cubre el caso.
3. Para inputs numéricos y monetarios, preferir `UInputNumber`.
4. Para disclosure/accordion, usar el componente oficial de Nuxt UI si existe en la versión instalada; no improvisar una versión casera sin verificar antes.

### Problemas a resolver

1. **Scroll excesivo**
   - Si el usuario agrega 8–10 conceptos, la página se vuelve demasiado larga.
2. **Lenguaje demasiado ERP**
   - “Líneas” no es el copy correcto para el usuario final.
3. **Inputs monetarios inconsistentes**
   - El usuario debe escribir valores humanos (`20000`) y ver moneda formateada, sin editar sufijos o prefijos técnicos.
4. **Comparación difícil entre conceptos**
   - Todos abiertos a la vez reducen escaneabilidad.

### Decisión UX cerrada

Implementar patrón **accordion por concepto**, no tabs.

#### Por qué no tabs

- ocultan demasiado contexto,
- escalan mal si hay muchos conceptos,
- dificultan comparar importes y descripciones,
- empeoran experiencia móvil.

#### Por qué sí accordion

- mantiene visión global,
- reduce scroll visible,
- permite editar un concepto sin abrir todos,
- funciona mejor con cantidades variables de conceptos.

### Estructura objetivo

#### Estado colapsado por concepto

Cada concepto cerrado debe mostrar una sola fila o bloque compacto con:

- índice (`Concepto 1`)
- descripción resumida o placeholder si está vacío
- cantidad
- valor estimado del concepto
- estado visual si está incompleto

#### Estado expandido por concepto

Al abrirlo, debe mostrar:

- descripción del concepto
- cantidad
- valor unitario
- subtotal/valor estimado del concepto
- acción eliminar

#### Comportamiento recomendado

- por defecto, abrir el último concepto recién agregado
- permitir solo **un concepto expandido a la vez** en v1 para limitar ruido visual
- mantener los demás colapsados

### Componentes candidatos

Evaluar y usar según Nuxt UI oficial:

- `UAccordion` o equivalente oficial para expand/collapse
- `UInputNumber` para cantidad y valor unitario
- `UButton` para acciones de agregar/eliminar
- `UBadge` o texto semántico simple para estados de incompleto

### Reglas obligatorias de implementación

#### 1. No romper el shape del backend

Frontend puede reorganizar completamente la UI, pero debe seguir serializando a:

- `description`
- `quantity`
- `unit_amount_cents`
- `sort_order`

#### 2. Dinero: valor visible, persistencia en centavos

El estado editable del formulario puede usar unidad monetaria visible, pero:

- `serializeQuotePayload()` convierte a centavos,
- `hydrateQuoteForm()` convierte de centavos a valor visible,
- previews usan `formatMoney()`,
- backend sigue siendo la única fuente final de verdad.

#### 3. Cantidad sin scroll accidental

`quantity` debe:

- evitar incremento/decremento accidental por rueda,
- usar step coherente con la decisión de negocio vigente,
- no invertir comportamiento de wheel,
- respetar el tipo de cantidad definido para la fase.

> Si la decisión del dominio es “cantidad entera” para esta fase, el componente debe reflejarlo explícitamente.

#### 4. Prefijo monetario no editable

Si se usa `$` o `COP`, debe ser visual, no parte del texto editable.

#### 5. Accesibilidad

Cada concepto debe conservar:

- `id` y `name` estables por campo,
- labels visibles,
- botón de expandir con nombre accesible,
- botón de eliminar con nombre accesible,
- foco predecible al abrir/cerrar.

### Propuesta de archivos

Sin imponer nombres exactos, el split recomendado es:

```txt
components/quotes/ui/
├── QuoteConceptsAccordion.vue
├── QuoteConceptCard.vue
└── QuoteConceptSummaryRow.vue
```

`QuoteLinesEditor.vue` puede sobrevivir como nombre técnico si su responsabilidad visible ya es “conceptos”, pero el copy interno y del template debe migrar a concepto.

### Riesgos de regresión a vigilar

1. pérdida de `sort_order`
2. subtotales que solo actualizan al blur y no en vivo
3. monto visible correcto pero `unit_amount_cents` incorrecto al serializar
4. accordion que desmonta inputs y pierde datos al colapsar
5. comportamiento diferente entre desktop y móvil
6. degradación de typecheck por wrappers no tipados

### Aceptación funcional

Debe cumplirse todo:

1. con 10 conceptos la pantalla sigue siendo usable y escaneable
2. el usuario puede identificar rápidamente cada concepto sin abrirlos todos
3. el valor estimado por concepto y total se actualizan en vivo
4. el input monetario acepta valores humanos y los formatea correctamente
5. no hay prefijos monetarios editables
6. no hay scroll wheel accidental en cantidad o dinero
7. el payload enviado al backend mantiene `unit_amount_cents`

### Revalidación obligatoria

Ejecutar y reportar:

```bash
bash scripts/validate-touched-files.sh <archivos_quotes_frontend...>
cd api && php artisan test --filter=QuoteApiTest
pnpm --filter @freelance/web exec nuxi typecheck
```

Y además prueba manual:

1. crear 1 concepto
2. crear 10 conceptos
3. colapsar/expandir sin perder datos
4. escribir montos grandes (`20000`, `1500000`)
5. confirmar que previews y total reaccionan en vivo
6. guardar cotización y verificar que backend persiste valores correctos
