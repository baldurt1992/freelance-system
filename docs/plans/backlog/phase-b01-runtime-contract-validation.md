# Backlog B01 — Validación runtime de contratos en frontend

**Rama:** `refactor/runtime-contract-validation`  
**Prerrequisito:** MVP main estable; no mezclar con cambios funcionales.

---

## Objetivo

Validar respuestas críticas del backend con schemas de `packages/contracts`, no solo con tipos TS.

---

## Leer primero

1. [README.md](./README.md)
2. [../WORKFLOW.md](../WORKFLOW.md)
3. [../../main/ARCHITECTURE.md](../../main/ARCHITECTURE.md) §6–8
4. [../../main/FRONTEND_ARCHITECTURE.md](../../main/FRONTEND_ARCHITECTURE.md)
5. [../../main/ENGINEERING_GUARDRAILS.md](../../main/ENGINEERING_GUARDRAILS.md)
6. `packages/contracts/README.md`
7. `apps/web/app/composables/api/useApi.ts`

---

## Qué sí hacer

1. Parsear responses críticas con schemas Zod existentes.
2. Empezar por bordes de alto valor:
   - auth
   - quotes
   - projects
   - finances
3. Centralizar la validación para no duplicar `.parse()` manual en cada page.
4. Mantener errores de contrato distinguibles de errores HTTP normales.

---

## Qué no hacer

1. No duplicar shapes TS fuera de `packages/contracts`.
2. No reescribir `useApi` para validar todo indiscriminadamente si eso complica adopción.
3. No cambiar payloads ni responses del backend en esta fase salvo que haya drift real descubierto.
4. No mezclar este trabajo con rediseño UI o refactor de pages.

---

## Coherencia que no se rompe

1. `packages/contracts` sigue siendo la única fuente de verdad del shape JSON.
2. `useApiError` sigue siendo la vía de errores UX; la validación runtime no debe introducir toasts ad hoc.
3. Los módulos actuales deben seguir consumiendo el mismo contrato público.

---

## Paso 0 — Git

```bash
git checkout main && git pull origin main
git checkout -b refactor/runtime-contract-validation
git push -u origin refactor/runtime-contract-validation
```

---

## Paso 1 — Diseño del borde validado

Definir una estrategia cerrada y consistente:

1. helper compartido tipo `parseApiResponse(schema, data)`
2. integración mínima con `useApi` o con `useXApi`
3. error explícito para drift de contrato, loggable y detectable

No elegir una estrategia distinta por módulo.

---

## Paso 2 — Adopción inicial

Aplicar primero en:

1. auth (`me`, login response, tenant settings visibles en sesión)
2. quotes (`list`, `find`, transitions`)
3. projects (`list`, `find`, conversion/payment flows`)
4. finances (`summary`, `entries`)

Si aparece fricción, documentarla en el PR; no abrir más módulos en la misma rama.

---

## Paso 3 — Tests y verificación

1. typecheck limpio
2. flujos existentes siguen funcionando
3. si hay tests frontend del borde HTTP, añadir al menos uno con payload inválido

```bash
cd apps/web && pnpm exec nuxi typecheck
cd /repo-root && bash scripts/validate-touched-files.sh <archivos...>
```

---

## Definición de done

- responses críticas parseadas con schemas del paquete de contratos
- sin duplicación de shapes
- sin cambios públicos de API
- sin regressions de UX de error
