# Fase 4.5 — Manejo de errores (sistema completo)

**Rama:** `feature/error-handling-fase-4-5`  
**Prerrequisito:** Fase 4 mergeada en `main` (clientes + `packages/contracts`).  
**Relación con fase 5:** Puede hacerse **antes** de cotizaciones (recomendado) o en paralelo al inicio de fase 5; no bloquea el dominio, pero evita repetir toasts genéricos en cada módulo nuevo.

**Objetivo:** Un solo contrato de error API → una capa de parsing en el frontend → mensajes útiles para el usuario en **todas** las features (formularios, toasts, auth, listas, uploads).

---

## Leer primero

1. [WORKFLOW.md](./WORKFLOW.md)
2. [../main/ARCHITECTURE.md](../main/ARCHITECTURE.md)
3. `packages/contracts/src/common/api-error.ts` (ya existe `ApiErrorSchema`)
4. `apps/web/app/composables/api/useApi.ts`
5. `apps/web/app/pages/login.vue` — ya tiene `extractApiErrorMessage` local (referencia a unificar)

---

## Diagnóstico actual (fase 4 — clientes)

| Escenario                      | Comportamiento hoy                           | Qué debería pasar                                       |
| ------------------------------ | -------------------------------------------- | ------------------------------------------------------- |
| Crear cliente (`new.vue`)      | Toast fijo: "Error al crear cliente"         | 422 → mensajes por campo o `message` del backend        |
| Actualizar (`[id].vue`)        | "Error al actualizar" genérico               | Igual + distinguir 404 / 422 / 5xx                      |
| Avatar (`new.vue`, `[id].vue`) | "Error al subir avatar"                      | 422 en `avatar` → texto claro (tipo, 2 MB)              |
| Eliminar (`index.vue`)         | Genérico individual / masivo                 | 404 → "ya no existe"; batch → resumen parcial si aplica |
| Backend 422                    | Laravel `{ message, errors: { campo: [] } }` | El front **no** lee `errors` en clientes                |
| `useApi.ts`                    | `ofetch` sin normalizar errores              | Errores opacos (`statusCode`, `data`)                   |
| Login                          | Helper local `extractApiErrorMessage`        | Mismo helper global, sin duplicar                       |

**Conclusión:** El análisis del agente es correcto para clientes, pero el plan debe ser **transversal** (auth, settings, futuras fases 5–9).

---

## Arquitectura objetivo

### 1. Contrato API (backend + Zod)

Formato estándar JSON (Laravel validation / HTTP exceptions):

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field must be a valid email address."],
    "avatar": [
      "The avatar field must be an image.",
      "The avatar field must not be greater than 2048 kilobytes."
    ]
  }
}
```

- Mantener y usar `ApiErrorSchema` en `packages/contracts`.
- Opcional fase 4.5: documentar en español mensajes de validación en Form Requests (`lang/es` o `attributes` + reglas custom) — **solo tenant API**, sin cambiar estructura JSON.

### 2. Backend — consistencia mínima

| Regla         | Acción                                                                                                              |
| ------------- | ------------------------------------------------------------------------------------------------------------------- |
| Validación    | Siempre Form Request; nunca `$request->validate()` suelto en controller                                             |
| 404 agregados | `ClientService` / futuros services → excepción de dominio o `ModelNotFoundException` → JSON 404 con `message` claro |
| Auth          | Ya usa `ValidationException` en login                                                                               |
| Respuestas    | `Accept: application/json` (el front ya lo envía)                                                                   |

**No** implementar un formato de error distinto al de Laravel en esta fase; adaptar el front al estándar existente.

### 3. Frontend — capa única (composables)

Nuevos archivos propuestos:

```
apps/web/app/composables/api/
  useApi.ts              # existente — añadir onResponseError opcional / rethrow normalizado
  parseApiError.ts       # puro: unknown → ParsedApiError
  useApiError.ts         # helpers UI: toast, primer mensaje, errores por campo
```

#### `ParsedApiError` (tipo)

| Campo         | Uso                                                                                                |
| ------------- | -------------------------------------------------------------------------------------------------- |
| `kind`        | `validation` \| `unauthorized` \| `forbidden` \| `not_found` \| `server` \| `network` \| `unknown` |
| `status`      | HTTP status (si existe)                                                                            |
| `message`     | Mensaje principal para toast o alerta                                                              |
| `fieldErrors` | `Record<string, string[]>` para formularios                                                        |
| `raw`         | Referencia al error original (logs, Sentry futuro)                                                 |

#### Mapeo `kind` ← status (global)

| Status  | `kind`         | Mensaje fallback (es) si no hay `message`              |
| ------- | -------------- | ------------------------------------------------------ |
| 0 / red | `network`      | No se pudo conectar con el servidor. Intenta de nuevo. |
| 401     | `unauthorized` | Sesión expirada. Vuelve a iniciar sesión.              |
| 403     | `forbidden`    | No tienes permiso para esta acción.                    |
| 404     | `not_found`    | El recurso no existe o fue eliminado.                  |
| 422     | `validation`   | Revisa los datos del formulario.                       |
| 429     | `server`       | Demasiados intentos. Espera un momento.                |
| 5xx     | `server`       | Error del servidor. Intenta más tarde.                 |

#### `parseApiError(error: unknown)`

1. Detectar error de `ofetch` (`statusCode` + `data`).
2. Validar `data` con `ApiErrorSchema.safeParse` (Zod).
3. Si hay `errors`, exponer `fieldErrors`; `message` = primer error de campo **o** `data.message`.
4. Función auxiliar: `getFieldError(fieldErrors, 'avatar')` para uploads.

#### `useApiError()` (UI)

- `toastApiError(error, { title?, fallback? })` — un toast Nuxt UI con color `error`.
- `applyFieldErrorsToForm(fieldErrors, setFieldError)` — integración con formularios (ver abajo).
- `logApiError(tag, error, context?)` — log estructurado según regla del proyecto (`[ClientsCreate]`, ids, sin tokens).

### 4. Patrones por tipo de pantalla

| Patrón                   | Implementación                                                                                    |
| ------------------------ | ------------------------------------------------------------------------------------------------- |
| **Formulario CRUD**      | En `catch`: `toastApiError` + opcional marcar campos (`UForm` / estado local por campo)           |
| **Solo acción (delete)** | `toastApiError` con `fallback` contextual ("Error al eliminar cliente")                           |
| **Batch delete**         | Si `Promise.all` falla: mensaje genérico + log con `ids`; fase futura: endpoint batch con detalle |
| **Upload avatar**        | Priorizar `getFieldError(..., 'avatar')`                                                          |
| **Login**                | Reemplazar `extractApiErrorMessage` local por `parseApiError` + mensaje en `errorMessage`         |

### 5. Integración con formularios (Nuxt UI)

- Corto plazo: toast con **primer** error de validación (mejora inmediata).
- Medio plazo (misma fase si da tiempo): pasar `fieldErrors` a `ClientFormFields` vía props `errors` y mostrar bajo cada `UInput`.
- Convención: nombres de campo API = nombres en form (`email`, `name`, `avatar`).

---

## Paso 0 — Git

```bash
git checkout main && git pull origin main
git checkout -b feature/error-handling-fase-4-5
git push -u origin feature/error-handling-fase-4-5
```

---

## Paso 1 — Contratos

**Archivo:** `packages/contracts/src/common/api-error.ts`

- Asegurar `ApiErrorSchema` exportado.
- Añadir tipo `ParsedApiErrorKind` (union) y función helper opcional `parseApiErrorBody(data: unknown)` si conviene compartir con tests.

```bash
cd packages/contracts && pnpm exec tsc --noEmit
```

---

## Paso 2 — Frontend: `parseApiError` + `useApiError`

1. Crear `apps/web/app/composables/api/parseApiError.ts` (lógica pura + tests unitarios opcionales con Vitest si ya está en el proyecto; si no, prueba manual documentada).
2. Crear `apps/web/app/composables/api/useApiError.ts` (toast + helpers).
3. Ajustar `useApi.ts`:
   - No tragar errores; opcional log en dev.
   - Documentar que los consumidores usan `parseApiError` en `catch`.

**Verificación:**

```bash
cd apps/web && pnpm exec nuxi typecheck
```

---

## Paso 3 — Migrar pantallas existentes

Orden sugerido (de mayor impacto a plantilla):

| #   | Archivo                                | Cambio                                                    |
| --- | -------------------------------------- | --------------------------------------------------------- |
| 1   | `pages/login.vue`                      | Eliminar `extractApiErrorMessage` local → `parseApiError` |
| 2   | `pages/clients/new.vue`                | `toastApiError` + errores de campo en form si hay tiempo  |
| 3   | `pages/clients/[id].vue`               | create/update/avatar                                      |
| 4   | `pages/clients/index.vue`              | delete individual; batch con fallback claro               |
| 5   | Revisar `settings`, `inbox` (template) | Solo si usan `api()` con catch genérico                   |

**Mensajes fallback contextual** (ejemplos, no hardcodear status en cada página):

- Crear: `No se pudo crear el cliente.`
- Actualizar: `No se pudo guardar los cambios.`
- Avatar: `No se pudo subir la imagen.`
- Eliminar: `No se pudo eliminar el cliente.`

El helper debe **preferir** siempre el mensaje del backend cuando exista.

---

## Paso 4 — Backend (mensajes en español, opcional pero recomendado)

1. `api/lang/es/validation.php` (o attributes en Form Requests) para campos: `name`, `email`, `phone`, `tax_id`, `avatar`.
2. Revisar que `UploadClientAvatarRequest` devuelva reglas que mapeen a `avatar` en `errors`.

**Verificación:**

```bash
cd api && php artisan test --filter=ClientApiTest
# Manual: POST cliente sin nombre → 422 JSON con errors.name
```

---

## Paso 5 — Documentación y convención para fases 5–9

1. Añadir sección **"Errores API"** en `docs/main/ARCHITECTURE.md` (enlace a este plan).
2. En cada plan futuro (5–9), checklist obligatorio:
   - [ ] Form Requests en backend
   - [ ] `catch` usa `toastApiError` / `parseApiError`
   - [ ] Sin strings fijos si el backend puede devolver detalle
3. Actualizar `AGENTS.md` o skill si aplica: "nunca toast genérico sin parsear error API".

---

## Criterios de aceptación (fase cerrada)

- [ ] Un solo `parseApiError` usado en login + clientes (sin duplicar helpers).
- [ ] 422 en crear/editar cliente muestra al menos un mensaje del backend (toast o campo).
- [ ] Avatar inválido o > 2 MB muestra mensaje derivado de `errors.avatar` o equivalente.
- [ ] Delete de cliente inexistente (404) muestra mensaje de recurso no encontrado, no genérico vacío.
- [ ] Sin conexión / 500 muestran fallback de red/servidor definidos en la tabla global.
- [ ] `pnpm exec nuxi typecheck` en `apps/web` OK.
- [ ] Tests API tenant sin regresiones.

---

## Qué NO entra en 4.5 (fases futuras)

| Tema                                               | Fase sugerida                    |
| -------------------------------------------------- | -------------------------------- |
| Interceptor global + redirect 401 automático       | 4.5 o 5 (decidir al implementar) |
| Sentry / observabilidad                            | Post fase 9 o infra              |
| Batch delete con reporte parcial                   | Dominio clientes o API dedicada  |
| i18n completo en frontend                          | Pulido UI post fase 9            |
| Errores de negocio custom (`409`, códigos propios) | Por dominio (quotes, billing)    |

---

## Verificación final (antes de PR)

```bash
git checkout main && git pull   # solo para comparar; trabajar en la rama feature
cd packages/contracts && pnpm exec tsc --noEmit
cd api && php artisan test
cd apps/web && pnpm exec nuxi typecheck
```

**Manual en navegador (`personal.localhost`):**

1. Crear cliente con email inválido → mensaje útil.
2. Subir archivo no imagen o > 2 MB → mensaje en avatar.
3. Eliminar cliente ya borrado (otra pestaña) → 404 claro.
4. Detener API y guardar → mensaje de red.

---

## Merge

Seguir [WORKFLOW.md](./WORKFLOW.md) § D–E: PR a `main`, borrar rama `feature/error-handling-fase-4-5` tras merge.

**Después del merge:** Fase 5 (cotizaciones) arranca con los composables de error ya disponibles; copiar patrón de `clients` en forms y toasts.
