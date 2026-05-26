# Backlog B05 — Revisión de jobs tenant-aware

**Rama:** `refactor/tenant-aware-jobs-review`  
**Prerrequisito:** atacar cuando se toque billing, colas o workers persistentes.

---

## Objetivo

Confirmar explícitamente que los jobs con side effects tenant siguen siendo seguros fuera del lifecycle del request.

---

## Leer primero

1. [README.md](./README.md)
2. [../WORKFLOW.md](../WORKFLOW.md)
3. [../../main/TENANCY.md](../../main/TENANCY.md)
4. [../../main/ARCHITECTURE.md](../../main/ARCHITECTURE.md) §5 y §8
5. [../../main/ENGINEERING_GUARDRAILS.md](../../main/ENGINEERING_GUARDRAILS.md)
6. `api/config/tenancy.php`
7. jobs activos que toquen modelos tenant

---

## Qué sí hacer

1. Revisar jobs que leen/escriben modelos tenant.
2. Verificar cómo se inicializa tenancy en workers.
3. Documentar patrón oficial para jobs tenant-aware del proyecto.
4. Ajustar código solo si el patrón actual depende de supuestos frágiles.

---

## Qué no hacer

1. No reimplementar Stancl bootstrappers.
2. No cambiar semántica de negocio de billing, quotes o projects en esta fase.
3. No convertir esta revisión en una migración de infraestructura de colas completa.

---

## Coherencia que no se rompe

1. Jobs siguen siendo side effects de casos de uso, no nuevo dominio.
2. Tenant DB nunca se toca desde contexto central por accidente.
3. Si un job usa snapshots históricos, se preserva ese comportamiento.

---

## Paso 0 — Git

```bash
git checkout main && git pull origin main
git checkout -b refactor/tenant-aware-jobs-review
git push -u origin refactor/tenant-aware-jobs-review
```

---

## Paso 1 — Inventario

Listar y revisar:

1. jobs que toquen billing
2. jobs que lean/escriban modelos tenant
3. puntos donde el worker podría ejecutar sin contexto tenant explícito

---

## Paso 2 — Ajuste/documentación

1. si el patrón actual es seguro, documentarlo
2. si no lo es, endurecerlo con el cambio mínimo posible
3. acompañar con tests o evidencia reproducible

---

## Paso 3 — Validación

```bash
cd api && php artisan test
cd /repo-root && bash scripts/validate-touched-files.sh <archivos...>
```

---

## Definición de done

- patrón tenant-aware explícito y documentado
- jobs sensibles revisados
- sin cambiar comportamiento funcional del dominio
