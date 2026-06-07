# Backlog técnico post-MVP

Objetivo: registrar y ejecutar deuda técnica **no bloqueante** del MVP, pero importante para escalar sin romper coherencia backend/frontend.

Estado actual: los módulos main del MVP están cerrados. Este backlog endurece arquitectura, validación, cobertura y disciplina operativa.
Histórico: B01–B05 fueron ejecutados y mergeados a `main` el **2026-05-26**.

## Cómo usar esta carpeta

1. Elegir **un solo** punto del backlog.
2. Abrir rama dedicada.
3. Seguir el runbook del punto correspondiente.
4. No mezclar dos puntos del backlog en la misma rama salvo que el propio runbook lo pida explícitamente.

## Índice de runbooks

| Backlog | Archivo | Rama sugerida | Prioridad | Estado |
| --- | --- | --- | --- | --- |
| B01 | [phase-b01-runtime-contract-validation.md](./phase-b01-runtime-contract-validation.md) | `refactor/runtime-contract-validation` | Alta | ✅ Hecho |
| B02 | [phase-b02-tenant-controller-consistency.md](./phase-b02-tenant-controller-consistency.md) | `refactor/tenant-controller-consistency` | Media-alta | ✅ Hecho |
| B03 | [phase-b03-frontend-pages-second-pass.md](./phase-b03-frontend-pages-second-pass.md) | `refactor/frontend-pages-second-pass` | Media | ✅ Hecho |
| B04 | [phase-b04-ui-interaction-coverage.md](./phase-b04-ui-interaction-coverage.md) | `test/ui-interaction-coverage` | Media | ✅ Hecho |
| B05 | [phase-b05-tenant-aware-jobs-review.md](./phase-b05-tenant-aware-jobs-review.md) | `refactor/tenant-aware-jobs-review` | Media-baja | ✅ Hecho |

## Guardrails comunes para cualquier backlog

1. No romper contratos públicos sin pasar primero por `packages/contracts`.
2. No reabrir módulos MVP estables con refactors cosméticos.
3. No mezclar rediseño visual con endurecimiento técnico.
4. Si el punto toca UI:
   - leer `docs/main/FRONTEND_ARCHITECTURE.md`
   - validar componentes Nuxt UI antes de introducir variantes nuevas
   - preservar baseline visual/UX ya estable del módulo
5. Si el punto toca backend:
   - controllers delgados
   - servicios orquestadores
   - tenancy siempre dentro del boundary Stancl
6. Si el punto toca dinero, impuestos o documentos:
   - `MoneyMath` sigue siendo la única fuente de cálculo
   - snapshots históricos no se recalculan retroactivamente

## Referencias compartidas

- Git: [../WORKFLOW.md](../WORKFLOW.md)
- Arquitectura: `docs/main/ARCHITECTURE.md`
- Tenancy: `docs/main/TENANCY.md`
- Guardrails: `docs/main/ENGINEERING_GUARDRAILS.md`
- Frontend: `docs/main/FRONTEND_ARCHITECTURE.md`

## Regla de uso

Este backlog no reemplaza el análisis. Cada punto debe ejecutarse como:

`análisis -> plan corto -> ejecución -> validación`

Si una tarea del backlog empieza a abrir demasiados frentes, cortarla y dividirla antes de seguir.
