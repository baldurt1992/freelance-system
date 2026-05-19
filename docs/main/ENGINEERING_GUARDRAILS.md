# Engineering Guardrails

Objetivo: cambios coherentes, diff mínimo, tenancy y dinero seguros.

---

## 1. Comportamiento obligatorio

1. Preservar el patrón arquitectónico más cercano salvo refactor explícito.
2. Preferir el **diff más pequeño** seguro.
3. Reutilizar servicios/composables antes de crear nuevos.
4. **No cambiar contratos** en silencio (API, props, emits, significado de campos DB).
5. No mezclar fixes no relacionados en un mismo cambio.
6. Si hay ediciones inesperadas en un archivo, detenerse y preguntar.

---

## 2. Propiedad por capa

| Capa                 | Responsabilidad                               |
| -------------------- | --------------------------------------------- |
| Vue / Nuxt           | Render, interacción, orquestación de pantalla |
| Composables          | Estado y flujo reutilizable                   |
| `packages/contracts` | Shape JSON                                    |
| Controllers          | HTTP + delegación                             |
| Form Requests        | Validación entrada                            |
| Application Services | Reglas de negocio y transacciones             |
| `MoneyMath`          | Cálculos monetarios                           |
| Stancl               | Contexto tenant únicamente                    |

---

## 3. Tenancy (Stancl)

1. Rutas en `routes/tenant.php` asumen tenancy inicializado.
2. Rutas central en `routes/api.php` **no** cargan modelos tenant sin `$tenant->run()`.
3. Jobs que tocan BD tenant deben ser tenant-aware.
4. Tests de feature tenant usan helper documentado en [TENANCY.md](./TENANCY.md).

---

## 4. Dinero

1. Enteros en centavos en servicios y BD.
2. `formatMoney` solo en UI.
3. Sin aritmética float para importes.
4. Sin atajos `* 1.19` fuera de `MoneyMath`.
5. Si `tax_enabled` es false → `tax_rate = 0` en cálculo; no confiar en totales del cliente.

---

## 5. Contratos API

1. Cambio de shape → primero `packages/contracts`.
2. Resource/Request Laravel alineados al schema.
3. Composables consumen tipos del pa contracts, no `any` en bordes públicos.

---

## 6. Refactor backend

Un método es **sobrecargado** si mezcla 3+ de:

- validación / guards
- normalización de payload
- persistencia de líneas
- totales / side effects
- conversión entre agregados (quote → project)

Acción: extraer al menos una responsabilidad antes de añadir ramas.

---

## 7. Refactor frontend (composables)

| Umbral          | Acción                    |
| --------------- | ------------------------- |
| ~250 LOC        | Revisar extracción        |
| ~500 LOC        | Plan de split obligatorio |
| Función ~60 LOC | Extraer helpers puros     |

Orden: contrato → helpers puros → hydrate/serialize → tipar bordes → adelgazar orquestador.

`misc/` no es basurero: si supera 6 archivos, dividir por responsabilidad.

---

## 8. Validación mínima

Tras tocar código:

```bash
bash scripts/validate-touched-files.sh <archivos...>
```

- PHP: `php -l`
- Frontend: `pnpm exec nuxi typecheck` (cuando exista `apps/web`)

Reportar: comando, resultado, bloqueador si falla.

---

## 9. Errores UX

- 422 → errores por campo en UI.
- No exponer stack traces en toasts.
- Reutilizar normalizador central de errores (cuando exista `useErrorHandler`).

---

## 10. Datos sensibles

- No commitear `.env`, tokens, claves.
- Solo `*.example` en git.

---

## 11. Contexto de feature

Para trabajo mediano/grande: `docs/plans/<feature>.md` con objetivo, alcance, archivos, validación, riesgos.
