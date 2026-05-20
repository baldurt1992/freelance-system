# Freelance System

Sistema de gestión para trabajo freelance: clientes, cotizaciones, proyectos, pagos, finanzas (ingresos/gastos y balance mensual) y cuentas de cobro.

## Stack

| Capa      | Tecnología                           |
| --------- | ------------------------------------ |
| Frontend  | Nuxt 4 + Nuxt UI (`apps/web`)        |
| API       | Laravel 13 + Stancl Tenancy (`api/`) |
| Contratos | `packages/contracts` (Zod)           |
| Datos     | MySQL 8 (central + BD por tenant)    |
| Cache     | Redis 7 (tags por tenant)            |
| Auth      | Sanctum Bearer                       |

## Empezar aquí

**Guía única de setup local:** [docs/main/ONBOARDING.md](docs/main/ONBOARDING.md)

```bash
cp api/.env.example api/.env
cp apps/web/.env.example apps/web/.env
bash scripts/setup-dev-hosts.sh
```

| App | README (detalle por paquete)             |
| --- | ---------------------------------------- |
| API | [api/README.md](api/README.md)           |
| Web | [apps/web/README.md](apps/web/README.md) |

## Documentación

- [Arquitectura](docs/main/ARCHITECTURE.md)
- [UI y rutas](docs/main/UI_ROUTES.md)
- [Finanzas](docs/main/FINANCES.md)
- [Tenancy (Stancl)](docs/main/TENANCY.md)
- [Guardrails](docs/main/ENGINEERING_GUARDRAILS.md)
- [Planes por fase](docs/plans/README.md) · [Flujo Git](docs/plans/WORKFLOW.md)
- [Bootstrap 0–2](docs/plans/bootstrap.md)
- [AGENTS.md](AGENTS.md)

## Estado

Fases **0–2** listas. Siguiente: **Fase 4 — Clients** ([bootstrap](docs/plans/bootstrap.md)).
