# Backlog B04 — Cobertura de interacción UI en flujos sensibles

**Rama:** `test/ui-interaction-coverage`  
**Prerrequisito:** definir y respetar stack de pruebas UI ya disponible en el repo.

---

## Objetivo

Agregar cobertura de interacción en frontend para reducir dependencia de validación manual en flujos sensibles.

---

## Leer primero

1. [README.md](./README.md)
2. [../WORKFLOW.md](../WORKFLOW.md)
3. [../../main/FRONTEND_ARCHITECTURE.md](../../main/FRONTEND_ARCHITECTURE.md)
4. [../../main/UI_ROUTES.md](../../main/UI_ROUTES.md)
5. `apps/web/README.md`

Si se usa Playwright en el repo, revisar también el skill local aplicable.

---

## Qué sí hacer

1. Cubrir flujos reales del usuario, no implementación interna.
2. Empezar por casos con mayor valor de regresión:
   - crear proyecto manual
   - convertir cotización a proyecto
   - filtros de finanzas por mes/tipo
   - errores visibles en formularios
3. Mantener pruebas estables y legibles.

---

## Qué no hacer

1. No probar detalles frágiles de markup sin valor funcional.
2. No mezclar esta rama con refactor estructural del módulo bajo prueba.
3. No duplicar cobertura que ya exista a nivel feature/backend si no agrega valor de UI.

---

## Coherencia que no se rompe

1. Las pruebas deben reflejar rutas y UX reales del dashboard.
2. Los mocks o fixtures no deben inventar contratos fuera de `packages/contracts`.
3. Si un flujo falla por un problema de arquitectura, volverlo backlog aparte; no “arreglar de paso” en esta rama.

---

## Paso 0 — Git

```bash
git checkout main && git pull origin main
git checkout -b test/ui-interaction-coverage
git push -u origin test/ui-interaction-coverage
```

---

## Paso 1 — Definir harness

1. confirmar framework de pruebas UI disponible
2. fijar setup mínimo reproducible
3. documentar comandos reales en el PR si todavía no estaban claros

---

## Paso 2 — Casos iniciales

Implementar primero:

1. project manual create happy path
2. finance filters by month/type

Luego evaluar si entran los otros dos sin abrir demasiado alcance.

---

## Paso 3 — Validación

Ejecutar el runner UI real y además:

```bash
cd apps/web && pnpm exec nuxi typecheck
cd /repo-root && bash scripts/validate-touched-files.sh <archivos...>
```

---

## Definición de done

- al menos dos flujos UI sensibles cubiertos de extremo a extremo
- pruebas reproducibles localmente
- sin mezclar correcciones estructurales grandes en la misma rama
