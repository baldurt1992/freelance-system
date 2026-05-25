# Planes de implementación por fase

Cada archivo es un **runbook ejecutable** (rama Git → pasos → verificación → merge).  
Seguir **en orden**. No saltar fases.

| Fase         | Archivo                                                                             | Rama Git                          | Estado             |
| ------------ | ----------------------------------------------------------------------------------- | --------------------------------- | ------------------ |
| 0–2          | [bootstrap.md](./bootstrap.md)                                                      | —                                 | ✅ Hecho           |
| 3 (opcional) | [phase-03-infra-docker.md](./phase-03-infra-docker.md)                              | `feature/infra-docker-fase-3`     | Parcial / opcional |
| 4            | [phase-04-clients.md](./phase-04-clients.md) · [fixes](./phase-04-clients-fixes.md) | `feature/clients-fase-4`          | ✅ Hecho           |
| 4.5          | [phase-04.5-error-handling.md](./phase-04.5-error-handling.md)                      | `feature/error-handling-fase-4-5` | ✅ Hecho           |
| 5            | [phase-05-quotes.md](./phase-05-quotes.md)                                          | `feature/quotes-fase-5`           | ✅ Hecho (local)   |
| 6            | [phase-06-projects-payments.md](./phase-06-projects-payments.md)                    | `feature/projects-fase-6`         | Pendiente          |
| 7            | [phase-07-finances.md](./phase-07-finances.md)                                      | `feature/finances-fase-7`         | Pendiente          |
| 8            | [phase-08-billing.md](./phase-08-billing.md)                                        | `feature/billing-fase-8`          | Pendiente          |
| 9            | [phase-09-templates-tax.md](./phase-09-templates-tax.md)                            | `feature/templates-tax-fase-9`    | Pendiente          |

**Git (todas las fases):** [WORKFLOW.md](./WORKFLOW.md)

**Docs de dominio:** `docs/main/ARCHITECTURE.md`, `TENANCY.md`, `UI_ROUTES.md`, `FINANCES.md`, `PROJECTS.md`

**Después de fase 9:** pulido UI global (no es una fase de dominio en esta tabla).
