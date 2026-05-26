# Planes del proyecto

Este directorio quedó dividido en dos líneas de trabajo:

| Línea | Índice | Uso |
| --- | --- | --- |
| MVP | [mvp/README.md](./mvp/README.md) | Runbooks históricos y ejecutables de las fases funcionales 0–9 |
| Backlog técnico | [backlog/README.md](./backlog/README.md) | Runbooks post-MVP para endurecer arquitectura sin romper módulos main |

## Reglas globales

1. Leer primero el índice de la línea que vas a tocar.
2. Para Git, todas las ramas siguen [WORKFLOW.md](./WORKFLOW.md).
3. Ningún agente debe mezclar una fase MVP y un backlog técnico en la misma rama.
4. Si el cambio toca contratos, tenancy, dinero o UI, revisar antes:
   - `docs/main/ARCHITECTURE.md`
   - `docs/main/TENANCY.md`
   - `docs/main/ENGINEERING_GUARDRAILS.md`
   - `docs/main/FRONTEND_ARCHITECTURE.md`
5. El backlog técnico no reabre el MVP: endurece estructura, validación y mantenibilidad.

## Orden recomendado

- Si el trabajo es dominio o funcionalidad del sistema base: usar `mvp/`.
- Si el trabajo es disciplina técnica, refactor o cobertura post-MVP: usar `backlog/`.
