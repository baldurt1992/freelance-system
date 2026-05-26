# Backlog B02 — Consistencia en controllers tenant

**Rama:** `refactor/tenant-controller-consistency`  
**Prerrequisito:** no mezclar con nuevos endpoints de dominio.

---

## Objetivo

Reducir repetición estable en controllers tenant sin ocultar el flujo HTTP ni crear una abstracción mágica.

---

## Leer primero

1. [README.md](./README.md)
2. [../WORKFLOW.md](../WORKFLOW.md)
3. [../../main/ARCHITECTURE.md](../../main/ARCHITECTURE.md) §5
4. [../../main/TENANCY.md](../../main/TENANCY.md)
5. [../../main/ENGINEERING_GUARDRAILS.md](../../main/ENGINEERING_GUARDRAILS.md)

---

## Qué sí hacer

1. Identificar repetición real en controllers tenant activos.
2. Extraer helpers pequeños o convenciones locales para:
   - fetch + not found
   - envelopes repetidos de response
   - patterns de transición simples
3. Mantener readable cada endpoint desde el controller.

---

## Qué no hacer

1. No introducir una base controller genérica con hooks opacos.
2. No mover lógica de negocio desde servicios hacia helpers HTTP.
3. No mezclar esta fase con cambios de contratos o recursos JSON.
4. No tocar rutas central.

---

## Coherencia que no se rompe

1. Controllers siguen delegando; no absorben dominio.
2. Requests y Resources conservan su rol.
3. Tenancy sigue resolviéndose solo en rutas tenant o `$tenant->run()`.

---

## Paso 0 — Git

```bash
git checkout main && git pull origin main
git checkout -b refactor/tenant-controller-consistency
git push -u origin refactor/tenant-controller-consistency
```

---

## Paso 1 — Selección de alcance

Empezar solo por controllers con más repetición visible:

1. `ProjectController`
2. `QuoteController`
3. `FinanceController`
4. `SettingsController` solo si comparte patrón real

No abrir más controllers si la abstracción deja de ser obvia.

---

## Paso 2 — Refactor

1. consolidar patrones repetidos
2. evitar helpers que dependan del nombre del modelo o convenciones mágicas
3. mantener mensajes y códigos HTTP existentes salvo bug claro

---

## Paso 3 — Validación

```bash
cd api && php artisan test
cd /repo-root && bash scripts/validate-touched-files.sh <archivos...>
```

Añadir o ajustar tests solo si el refactor cambia una ruta de control sensible.

---

## Definición de done

- menos repetición estable en controllers tenant
- sin cambios públicos de API
- sin lógica de negocio movida al borde HTTP
