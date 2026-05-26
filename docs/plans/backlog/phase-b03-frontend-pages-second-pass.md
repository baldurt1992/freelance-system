# Backlog B03 — Segunda pasada de pages grandes en frontend

**Rama:** `refactor/frontend-pages-second-pass`  
**Prerrequisito:** no mezclar con features nuevas.

---

## Objetivo

Seguir adelgazando pages que todavía mezclan demasiada coordinación, presentación y lógica local.

---

## Leer primero

1. [README.md](./README.md)
2. [../WORKFLOW.md](../WORKFLOW.md)
3. [../../main/FRONTEND_ARCHITECTURE.md](../../main/FRONTEND_ARCHITECTURE.md)
4. [../../main/UI_ROUTES.md](../../main/UI_ROUTES.md)
5. [../../main/ENGINEERING_GUARDRAILS.md](../../main/ENGINEERING_GUARDRAILS.md)
6. `apps/web/app/pages/clients/index.vue` y `clients/*` como baseline

---

## Qué sí hacer

1. Extraer helpers puros primero.
2. Extraer `ui/` o `sections/` si la page mezcla layout grande.
3. Extraer composables solo cuando haya estado o flujo reutilizable real.
4. Mantener la page como coordinadora.

---

## Qué no hacer

1. No crear `tableManager` genérico o abstracciones transversales prematuras.
2. No cambiar UX, copy o rutas salvo que el refactor lo exija para corregir una incoherencia real.
3. No introducir componentes Nuxt UI sin verificar nombre/props.
4. No duplicar helpers de fecha, dinero, status o payloads si ya existen.

---

## Coherencia que no se rompe

1. La taxonomía del repo se mantiene:
   - `useXApi`
   - helpers puros
   - `useX`
   - componentes `ui/` y `sections/`
2. El catch de errores sigue pasando por `useApiError().toastApiError(...)`.
3. Formularios siguen usando `UFormField` y `UInputDate` donde aplique.

---

## Candidatos iniciales

1. `apps/web/app/pages/clients/[id].vue`
2. `apps/web/app/pages/quotes/[id].vue`
3. `apps/web/app/pages/settings/index.vue`

Trabajar en ese orden salvo que el contexto del repo cambie.

---

## Paso 0 — Git

```bash
git checkout main && git pull origin main
git checkout -b refactor/frontend-pages-second-pass
git push -u origin refactor/frontend-pages-second-pass
```

---

## Paso 1 — Split controlado

Para cada page:

1. medir responsabilidades mezcladas
2. extraer lo mínimo necesario
3. detenerse cuando la page quede razonablemente delgada

No intentar “perfección” total del módulo en una sola rama.

---

## Paso 2 — Validación

```bash
cd apps/web && pnpm exec nuxi typecheck
cd /repo-root && bash scripts/validate-touched-files.sh <archivos...>
```

Validación manual mínima:

- navegación
- submit/acciones principales
- rendering de estados vacíos/carga

---

## Definición de done

- pages candidatas con menos responsabilidades mezcladas
- sin regresión visual o de navegación
- sin abstracción genérica prematura
