# Fase 4 — Clientes + scaffold `packages/contracts`

**Rama:** `feature/clients-fase-4`  
**Prerrequisito:** `main` con fases 0–2 mergeadas.  
**Patrón:** contrato Zod → migración tenant → API → composables → páginas (sin modales como CRUD principal).

---

## Leer primero (obligatorio)

1. [WORKFLOW.md](./WORKFLOW.md)
2. [../main/ARCHITECTURE.md](../main/ARCHITECTURE.md) §5–7
3. [../main/UI_ROUTES.md](../main/UI_ROUTES.md) — rutas `/clients`
4. [../main/TENANCY.md](../main/TENANCY.md) — rutas tenant
5. [../../packages/contracts/README.md](../../packages/contracts/README.md)
6. [../../api/tests/TenantTestCase.php](../../api/tests/TenantTestCase.php)
7. Skills: `laravel-specialist`, `vue-best-practices`, `zod`, `freelance-tenancy-stancl`

---

## Paso 0 — Git

Ejecutar [WORKFLOW.md § A](./WORKFLOW.md) con:

```bash
git checkout -b feature/clients-fase-4
git push -u origin feature/clients-fase-4
```

---

## Paso 1 — Scaffold `packages/contracts`

1. Crear `packages/contracts/package.json`:
   - name: `@freelance/contracts`
   - dependencies: `zod`
   - devDependencies: `typescript`
   - exports: `./src/index.ts`
2. Crear `packages/contracts/tsconfig.json` (strict, ESNext).
3. Crear archivos:
   - `src/common/money.ts` — `MoneyCentsSchema` (int positivo), `CurrencyCodeSchema` (ej. `COP`)
   - `src/common/pagination.ts` — `PaginationMetaSchema`
   - `src/common/api-error.ts` — shape error API
   - `src/clients.ts` — ver Paso 2
   - `src/index.ts` — re-export todo
4. Añadir workspace en raíz si falta: `pnpm-workspace.yaml` debe incluir `packages/contracts`.
5. En `apps/web/package.json`: dependencia `"@freelance/contracts": "workspace:*"`.
6. Desde raíz: `pnpm install`.

**Verificación:**

```bash
cd packages/contracts && pnpm exec tsc --noEmit
```

---

## Paso 2 — Schema Zod `Client`

En `packages/contracts/src/clients.ts` definir:

| Campo        | Tipo Zod                          |
| ------------ | --------------------------------- |
| `id`         | `z.string().uuid()`               |
| `name`       | `z.string().min(1).max(255)`      |
| `email`      | `z.string().email().nullable()`   |
| `phone`      | `z.string().max(50).nullable()`   |
| `tax_id`     | `z.string().max(50).nullable()`   |
| `address`    | `z.string().max(500).nullable()`  |
| `notes`      | `z.string().max(2000).nullable()` |
| `created_at` | `z.string().datetime()`           |
| `updated_at` | `z.string().datetime()`           |

Exportar también:

- `ClientCreateSchema` (sin id, sin timestamps)
- `ClientUpdateSchema` (partial de create)
- `ClientListSchema` — `{ data: Client[], meta: PaginationMeta }`

---

## Paso 3 — Migración tenant `clients`

1. Crear migración en `api/database/migrations/tenant/`:
   - tabla `clients`
   - columnas alineadas al schema (UUID `id` o bigIncrements según convención del proyecto; **usar la misma convención que `users`**)
   - `timestamps`
2. Ejecutar en dev:
   ```bash
   cd api && php artisan tenants:migrate
   ```

**Verificación:** tabla `clients` existe en BD tenant `personal`.

---

## Paso 4 — Backend Laravel

### 4.1 Modelo

- `api/app/Models/Tenant/Client.php` (namespace coherente con User tenant)
- `$fillable` acorde al create schema

### 4.2 Application

- `api/app/Application/Clients/ClientService.php`
  - `list()`, `find()`, `create()`, `update()`, `delete()`
  - Sin lógica en controller

### 4.3 HTTP

- `StoreClientRequest` / `UpdateClientRequest` — reglas espejo de Zod
- `ClientResource` — JSON igual que `ClientSchema`
- `ClientController` en `Api/Tenant/`:
  - `GET /clients` (paginado)
  - `POST /clients`
  - `GET /clients/{id}`
  - `PUT /clients/{id}`
  - `DELETE /clients/{id}`

### 4.4 Rutas

Registrar en `api/routes/tenant.php` dentro de `auth:sanctum`.

### 4.5 Logs

En Service/Controller puntos clave: create/update/delete con `client_id` (formato `[Clients]`).

---

## Paso 5 — Tests API

1. `api/tests/Feature/Tenant/ClientApiTest.php` extends `TenantTestCase`
2. Casos mínimos:
   - list requiere auth
   - create + show + update + delete con token
   - validación 422 en email inválido
3. Usar host `http://test.localhost` como `TenantAuthTest`.

```bash
cd api && php artisan test --filter=ClientApiTest
```

---

## Paso 6 — Frontend Nuxt

### 6.1 Composables

- `apps/web/app/composables/clients/useClientsApi.ts` — CRUD HTTP con `useApi()`
- `apps/web/app/composables/clients/useClientForm.ts` — estado form create/edit
- Tipos importados de `@freelance/contracts`

### 6.2 Páginas (páginas completas, no modal principal)

- `pages/clients/index.vue` — tabla UTable + link a new + fila → detail
- `pages/clients/new.vue` — formulario crear
- `pages/clients/[id].vue` — detalle + editar inline o link a edit
- `pages/clients/[id]/edit.vue` — opcional si no editas en [id]

### 6.3 Navegación

- Actualizar menú lateral del dashboard: entrada **Clientes** → `/clients`
- Deprecar o redirigir `pages/customers.vue` → `/clients` (redirect en `nuxt.config` o reemplazar archivo)

### 6.4 Componentes

- `components/Clients/ui/ClientFormFields.vue` — campos tontos (props/emits)

**Verificación:**

```bash
pnpm typecheck:web
```

**Manual:** login en `http://personal.localhost:3000`, CRUD cliente contra API `personal.localhost:8000`.

---

## Paso 7 — Cierre

1. Marcar checklist en este archivo (mental o PR).
2. Ejecutar [WORKFLOW.md § C y D](./WORKFLOW.md).
3. Título PR sugerido: `feat: clients module and contracts package (phase 4)`

---

## Definition of Done

- [ ] Rama `feature/clients-fase-4` pusheada y PR abierto
- [ ] `packages/contracts` compila (`tsc --noEmit`)
- [ ] `php artisan test` verde (incl. ClientApiTest)
- [ ] `pnpm typecheck:web` verde
- [ ] CRUD clientes funciona en UI con Bearer tenant
- [ ] Sin `.env` commiteado
- [ ] Merge a `main` aprobado

---

## No hacer en esta fase

- Cotizaciones, proyectos, finanzas
- Rediseño visual pesado (solo UI funcional Nuxt UI)
- Rutas central landlord nuevas
