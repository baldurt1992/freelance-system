# Fase 4 — Correcciones post-auditoría (sin Git)

**Rama de trabajo:** `feature/clients-fase-4` (ya existente).  
**No incluye:** stage, commit, push ni PR — solo código y docs.

**Leer antes:** [phase-04-clients.md](./phase-04-clients.md), [../WORKFLOW.md](../WORKFLOW.md) § C (verificación).

---

## Orden de ejecución (obligatorio)

1. Backend menor (import, Form Request, test)
2. `.gitignore` (storage tenant)
3. Adelgazar frontend (composable + componentes)
4. Corregir doble paginación en lista
5. Actualizar `phase-04-clients.md`
6. Verificación final

---

## Paso 1 — Backend: limpiar controller

**Archivo:** `api/app/Http/Controllers/Api/Tenant/ClientController.php`

1. Eliminar línea `use Illuminate\Support\Facades\Storage;` (no se usa).
2. En `uploadAvatar`, dejar de validar inline; inyectar `UploadClientAvatarRequest` (Paso 2).

---

## Paso 2 — Backend: `UploadClientAvatarRequest`

1. Crear `api/app/Http/Requests/Client/UploadClientAvatarRequest.php`:

```php
// authorize(): return true;
// rules(): 'avatar' => ['required', 'image', 'max:2048'] (2048 KB)
```

2. En `ClientController::uploadAvatar`:
   - Firma: `uploadAvatar(UploadClientAvatarRequest $request, string $id)`
   - Usar `$request->validated()['avatar']` para `store('avatars', 'public')`.
3. Añadir `use App\Http\Requests\Client\UploadClientAvatarRequest;`

---

## Paso 3 — Backend: test listado autenticado

**Archivo:** `api/tests/Feature/Tenant/ClientApiTest.php`

Añadir método:

```php
#[Test]
public function list_returns_paginated_clients_when_authenticated(): void
{
    // POST crear un cliente con token
    // GET /api/v1/clients con Bearer
    // assertOk, assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']])
    // assertJsonCount(1, 'data') o >= 1
}
```

**Verificación:**

```bash
cd api && php artisan test --filter=ClientApiTest
```

Debe pasar **6 tests** en esa clase (o el total de tests tenant sin regresiones).

---

## Paso 4 — `.gitignore`: no versionar uploads tenant

**Archivo:** `.gitignore` (raíz)

Añadir bajo sección Laravel:

```gitignore
# Tenant uploaded files (local dev)
api/storage/tenant*/
```

No borrar archivos locales; solo evitar que entren al repo.

---

## Paso 5 — Adelgazar lista de clientes (SRP)

**Objetivo:** `pages/clients/index.vue` **< 120 líneas**; tabla y columnas fuera.

### 5.1 Crear `apps/web/app/composables/clients/useClientsTableColumns.ts`

- Exportar función `useClientsTableColumns(options)` donde `options` incluya:
  - `onNavigateDetail(id: number)`
  - `onEdit(id: number)`
  - `onDelete(row: Row<Client>)`
- Devolver `columns: TableColumn<Client>[]` (mover definición actual de `index.vue` líneas ~110–203).
- Usar `resolveComponent` para UButton, UDropdownMenu, UCheckbox, UAvatar igual que hoy.

### 5.2 Crear `apps/web/app/components/clients/ClientsListToolbar.vue`

**Props:**

- `searchQuery` (model o prop + emit `update:searchQuery`)
- `selectedCount: number`
- `tableRef` — ref de la tabla para menú columnas (o emitir eventos)

**Emits:**

- `confirm-batch-delete`

**Contenido:** barra superior actual (UInput búsqueda, ClientsDeleteModal + botón eliminar, dropdown columnas). Copiar desde `index.vue` template líneas ~233–282.

### 5.3 Reescribir `apps/web/app/pages/clients/index.vue`

**Mantener en la page:**

- `page`, `searchQuery`, `useAsyncData` + `useClientsApi`
- `rowSelection`, `columnVisibility`, `table` ref
- `onBatchDelete`, `refresh`, navegación
- Llamada a `useClientsTableColumns({ ... })`
- Template: `UDashboardPanel` + `ClientsListToolbar` + `UTable` + footer paginación

**No dejar en la page:** definición larga de `columns` ni toolbar markup.

---

## Paso 6 — Corregir doble paginación (crítico)

**Archivo:** `apps/web/app/pages/clients/index.vue` (tras Paso 5)

### Eliminar (script)

- `import { getPaginationRowModel } from "@tanstack/table-core"`
- `const pagination = ref({ pageIndex: 0, pageSize: 15 })`
- `watch(page, (p) => { pagination.value.pageIndex = p - 1 })`

### Eliminar (template en `<UTable>`)

- `v-model:pagination="pagination"`
- `:pagination-options="{ getPaginationRowModel: getPaginationRowModel() }"`

### Mantener

- Paginación **solo servidor** con `UPagination`:
  - `:default-page="page"`
  - `:items-per-page="meta.per_page"`
  - `:total="meta.total"`
  - `@update:page="page = $event"`
- `useAsyncData` con `page` en key y `list(page.value, searchQuery.value)`.

### Ajustar texto footer (opcional)

Cambiar contador de selección a algo claro:

```txt
{{ selectedCount }} seleccionada(s) en esta página ({{ clients.length }} filas)
```

O usar `meta.total` solo si muestras contexto global.

### Probar manual

1. Crear **> 15** clientes en dev (o bajar `per_page` temporalmente en API a 5 para probar).
2. Ir a `/clients`.
3. Página 2 del `UPagination` debe cargar **otros** clientes (network: `GET /clients?page=2`).
4. La tabla **no** debe mostrar paginador interno de TanStack (solo filas de la página actual).

---

## Paso 7 — Actualizar plan fase 4

**Archivo:** `docs/plans/mvp/phase-04-clients.md`

### 7.1 Paso 2 — tabla de campos

- Cambiar `id` de `z.string().uuid()` a `z.number().int().positive()`.
- Añadir fila opcional: `avatar` | `z.string().max(2048).nullable()` | **Incluido en fase 4**

### 7.2 Paso 3 — migraciones

- Mencionar dos migraciones si aplica: `create_clients_table` + `add_avatar_to_clients_table`.

### 7.3 Paso 4 — HTTP

- Añadir ruta: `POST /clients/{id}/avatar`
- Añadir `UploadClientAvatarRequest`

### 7.4 Paso 5 — tests

- Añadir: `list_returns_paginated_clients_when_authenticated`

### 7.5 Paso 6 — Frontend

- Ruta componentes: `components/clients/ui/ClientFormFields.vue`
- Añadir: `composables/clients/useClientsTableColumns.ts`
- Añadir: `components/clients/ClientsListToolbar.vue`
- Nota: **una sola paginación (servidor)** en `index.vue`; no `getPaginationRowModel`

### 7.6 Nueva sección al final

```markdown
## Correcciones post-auditoría

Ver [phase-04-clients-fixes.md](./phase-04-clients-fixes.md). Ejecutar antes de cerrar PR.
```

### 7.7 README plans

En `docs/plans/mvp/README.md`, bajo fase 4, enlace opcional: “fixes → phase-04-clients-fixes.md”.

---

## Paso 8 — Linter / tipos (obligatorio)

Tras cada cambio, ejecutar diagnósticos en:

- `api/app/Http/Controllers/Api/Tenant/ClientController.php`
- `apps/web/app/pages/clients/index.vue`
- `apps/web/app/components/clients/*.vue`
- `apps/web/app/composables/clients/*.ts`

**PHP:** inyectar `Request` en `index()` (no `request()` helper). Constantes HTTP con `Symfony\Component\HttpFoundation\Response as HttpResponse`.

**Vue:** tipar `useTemplateRef<ClientsTableExpose>` (`~/types/clients-table.ts`); evitar inferencia circular en `selectedCount`.

---

## Paso 9 — Verificación final (obligatorio)

Ejecutar en orden; **todos** deben pasar:

```bash
cd api && php artisan test
```

```bash
cd packages/contracts && pnpm exec tsc --noEmit
```

```bash
# Desde raíz, Node >= 22
pnpm typecheck:web
```

**Manual rápido:**

| #   | Acción                            | Esperado                 |
| --- | --------------------------------- | ------------------------ |
| 1   | `/clients` con >1 página de datos | UPagination cambia datos |
| 2   | Crear / editar / borrar cliente   | OK                       |
| 3   | Subir avatar en detalle           | URL en listado           |
| 4   | `customers`                       | Redirect a `/clients`    |

---

## Definition of Done (fixes)

- [ ] Sin import `Storage` muerto
- [ ] `UploadClientAvatarRequest` usado
- [ ] Test list autenticado
- [ ] `.gitignore` tenant storage
- [ ] `index.vue` < 120 líneas
- [ ] Sin doble paginación TanStack + servidor
- [ ] `phase-04-clients.md` actualizado
- [ ] Tests + typecheck verdes

---

## No hacer en este runbook

- Commits ni PR
- Refactor visual / diseño premium
- Fase 5 (quotes)
