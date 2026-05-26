# Backlog técnico post-MVP

Objetivo: registrar la deuda técnica consciente que **no bloquea** el MVP actual, pero conviene atender antes de que empiece a multiplicar costo de cambio.

Estado actual: `projects` y `finances` ya recibieron la primera pasada de refactor estructural en `refactor/post-mvp-projects-foundation`. Este backlog captura lo siguiente, en orden recomendado.

---

## 1. Contratos runtime en frontend

**Prioridad:** alta  
**Cuándo atacar:** si aparecen respuestas API ambiguas, bugs por drift backend/frontend o nuevos módulos con payloads más complejos.

### Objetivo

Validar respuestas críticas del backend con los schemas de `packages/contracts`, no solo con tipos TS estáticos.

### Alcance

- Incorporar parseo runtime en el borde HTTP/composables para respuestas críticas.
- Empezar por módulos sensibles:
  - `auth`
  - `quotes`
  - `projects`
  - `finances`
- Mantener los schemas existentes como fuente de verdad; no duplicar shapes.

### Resultado esperado

- Si el backend rompe un contrato, el frontend falla de forma explícita y trazable.
- Menor riesgo de regressions silenciosas en refactors futuros.

---

## 2. Reducción transversal de repetición en controllers tenant

**Prioridad:** media-alta  
**Cuándo atacar:** cuando se toque de nuevo `projects`, `quotes`, `finances` o `settings`.

### Objetivo

Reducir repetición de patrones `find + 404 + response` sin convertir los controllers en una abstracción opaca.

### Alcance

- Revisar controllers tenant más repetitivos.
- Extraer solo helpers pequeños o convenciones locales si la repetición es estable.
- No introducir una capa “mágica” genérica que esconda el flujo HTTP.

### Resultado esperado

- Controllers más cortos y consistentes.
- Menos ruido estructural al abrir nuevos endpoints.

---

## 3. Segunda pasada de pages grandes en frontend

**Prioridad:** media  
**Cuándo atacar:** antes de expandir módulos existentes o abrir CRUDs nuevos similares.

### Objetivo

Seguir adelgazando pages que todavía mezclan demasiada coordinación, presentación y lógica local.

### Candidatos iniciales

- `apps/web/app/pages/clients/[id].vue`
- `apps/web/app/pages/quotes/[id].vue`
- `apps/web/app/pages/settings/index.vue`

### Regla

- Extraer primero helpers puros, luego secciones/componentes, luego composables.
- No crear abstracciones genéricas hasta ver duplicación real entre al menos 2 módulos ordenados.

### Resultado esperado

- Pages más fáciles de mantener.
- Menor probabilidad de tocar múltiples archivos inseguros por cambios pequeños.

---

## 4. Cobertura de interacción UI en flujos sensibles

**Prioridad:** media  
**Cuándo atacar:** cuando se abra la siguiente ronda de estabilización UI o se introduzca Playwright.

### Objetivo

Cubrir con pruebas de interacción los flujos UI donde hoy dependemos principalmente de typecheck y validación manual.

### Flujos sugeridos

- Crear proyecto manual
- Convertir cotización a proyecto
- Filtros de finanzas por mes/tipo
- Estados de error visibles en formularios

### Resultado esperado

- Más confianza en refactors de composición Vue/Nuxt UI.
- Menor dependencia de regresión manual en formularios.

---

## 5. Revisión liviana de jobs tenant-aware

**Prioridad:** media-baja  
**Cuándo atacar:** al tocar billing, colas o despliegue productivo con workers persistentes.

### Objetivo

Confirmar explícitamente que los jobs con side effects tenant siguen siendo seguros y claros al ejecutarse fuera del request lifecycle.

### Alcance

- Revisar jobs que leen/escriben modelos tenant.
- Verificar inicialización de tenancy y supuestos del worker.
- Documentar el patrón oficial del proyecto para jobs tenant-aware.

### Resultado esperado

- Menos riesgo operativo al endurecer colas en producción.
- Menos dependencia de conocimiento implícito sobre Stancl.

---

## Regla de uso

Este backlog **no** es una nueva fase de dominio. Son mejoras de disciplina que se ejecutan solo cuando:

- desbloquean cambio futuro,
- reducen riesgo real en un módulo activo, o
- el costo de seguir postergando ya supera el costo del refactor.

Si una tarea del backlog se va a ejecutar, abrir rama dedicada y documentar alcance mínimo antes de tocar código.
