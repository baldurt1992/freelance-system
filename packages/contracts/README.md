# packages/contracts

Fuente de verdad para **formas JSON** entre Laravel (`api/`) y Nuxt (`apps/web/`).

## Principios

1. **Schema primero** — cambiar el contrato aquí antes que en API o UI.
2. **Tipos TS** — `z.infer<typeof Schema>` o generación con `openapi-typescript` si más adelante se exporta OpenAPI desde Laravel.
3. **PHP espejo** — Form Requests y API Resources deben producir/consumir el mismo shape (validar con tests de fixture).
4. **Versionado** — URL `/api/v1`; campos nuevos opcionales; breaking → v2.

## Estructura prevista

```txt
packages/contracts/
├── src/
│   ├── common/
│   │   ├── money.ts          # MoneyCents, CurrencyCode
│   │   ├── pagination.ts
│   │   └── api-error.ts
│   ├── clients.ts
│   ├── quotes.ts
│   ├── projects.ts
│   ├── payments.ts
│   ├── finances.ts
│   ├── billing.ts
│   └── index.ts
├── package.json
└── tsconfig.json
```

## Convenciones de campos

| Concepto          | Convención                                                                            |
| ----------------- | ------------------------------------------------------------------------------------- |
| Dinero            | `*_cents` (integer)                                                                   |
| IVA               | `tax_rate` (number, 0 si disabled), `tax_cents`, `tax_enabled` en tenant settings     |
| IDs               | Seguir el tipo real del recurso; hoy en tenant predomina `int` positivo. No asumir `uuid` sin decisión explícita |
| Fechas            | ISO 8601 string en JSON                                                               |
| Fuente movimiento | `source_type`, `source_id` (obligatorio en ingresos de proyecto; `manual` sin enlace) |

## Ejemplo (futuro)

```ts
import { z } from "zod";

export const ClientSchema = z.object({
  id: z.string().uuid(),
  name: z.string().min(1),
  email: z.string().email().nullable(),
  tax_id: z.string().nullable(),
  created_at: z.string().datetime(),
});

export type Client = z.infer<typeof ClientSchema>;
```

## Flujo de cambio

1. Editar schema Zod + export type.
2. Actualizar migración/request/resource PHP si aplica.
3. Actualizar composables que consumen el tipo.
4. Añadir/ajustar test con JSON fixture.

## Estado

Paquete activo en producción local del monorepo. `clients`, `quotes`, `projects`, `payments` y `finances` ya están implementados y exportados; usar este README como guía de evolución de contratos.
