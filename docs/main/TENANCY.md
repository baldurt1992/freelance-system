# Tenancy — Stancl + adaptación central/tenant

**Motor:** [stancl/tenancy](https://tenancyforlaravel.com/) v3  
**Aislamiento:** una base de datos MySQL por tenant  
**Inspiración operativa:** patrones probados en nexus-erp (sin Plesk ni provisioning pesado)

---

## 1. Modelo mental

```txt
                    ┌─────────────────────┐
                    │   MySQL central     │
                    │  tenants, domains   │
                    └──────────┬──────────┘
                               │
         ┌─────────────────────┼─────────────────────┐
         ▼                     ▼                     ▼
   ┌───────────┐         ┌───────────┐         ┌───────────┐
   │ tenant_db │         │ tenant_db │         │ tenant_db │
   │ personal  │         │ acme-sas  │         │  ...      │
   └───────────┘         └───────────┘         └───────────┘
```

- **Central (landlord):** metadatos del tenant, dominios, futuro billing SaaS.
- **Tenant:** clientes, cotizaciones, proyectos, pagos, finanzas, usuarios del workspace.

---

## 2. Qué hace Stancl (no reimplementar)

- Bootstrappers: conexión `tenant`, cache/filesystem/queue con prefijo.
- `php artisan tenants:migrate`, `tenants:run`.
- Pipeline de creación de BD al registrar tenant.
- Helpers de test: `$tenant->run(fn () => ...)`.

---

## 3. Qué adaptamos (mejor que copiar nexus)

| Nexus (ERP)                       | Freelance System                                  |
| --------------------------------- | ------------------------------------------------- |
| `DetectTenant` manual             | Middleware stack Stancl + exclusión rutas central |
| `TenancyManager` custom           | Bootstrappers Stancl + `ApplicationContext` fino  |
| `TenantCreationService` + Plesk   | `php artisan tenant:provision {slug}`             |
| Subdominio obligatorio en dev     | `{slug}.localhost` + `DEFAULT_TENANT_SLUG`        |
| Dos modelos Sanctum master/tenant | v1: usuarios solo en BD tenant; central mínimo    |

---

## 4. Rutas HTTP

### Central — **sin** inicializar tenancy

Prefijo sugerido: `/api/v1/central`

| Método | Ruta              | Uso                      |
| ------ | ----------------- | ------------------------ |
| GET    | `/central/health` | Healthcheck              |
| POST   | `/central/...`    | Futuro: admin plataforma |

Archivo: `routes/api.php` (grupo sin middleware `tenancy`).

### Tenant — **con** tenancy

Prefijo: `/api/v1` en `routes/tenant.php`

| Área         | Rutas                                                   |
| ------------ | ------------------------------------------------------- |
| Auth         | `POST /auth/login`, `GET /auth/me`, `POST /auth/logout` |
| Clientes     | `/clients`                                              |
| Cotizaciones | `/quotes`                                               |
| Proyectos    | `/projects`                                             |
| Pagos        | `/projects/{id}/payments`                               |
| Finanzas     | `/finances` (entries, summary)                          |
| Cobros       | `/billing-documents`                                    |

Middleware típico Stancl (ajustar en scaffold):

- `InitializeTenancyByDomain` o `BySubdomain`
- `PreventAccessFromCentralDomains` en rutas tenant
- `auth:sanctum` en rutas protegidas

---

## 5. Identificación del tenant

| Entorno        | Mecanismo                                                        |
| -------------- | ---------------------------------------------------------------- |
| **Producción** | Host `{slug}.tudominio.com` en `domains.domain`                  |
| **Local**      | `personal.localhost` (API + front) → `InitializeTenancyByDomain` |
| **Tests**      | `$this->tenant->run()` o helper `actingAsTenant()`               |

### Desarrollo local

1. Entrada en `/etc/hosts`: `127.0.0.1 personal.localhost`
2. `DEFAULT_TENANT_SLUG=personal` en `.env` (documentación; el slug real viene del host).
3. Nuxt `vite.server.allowedHosts` incluye `.localhost`.

**No usar** header `X-Tenant` en producción salvo herramientas internas; el contrato oficial es **subdominio**.

---

## 6. Auth Bearer + subdominios

1. Cliente resuelve API: `https://{tenant}.api.tudominio.com` o mismo host con subdominio.
2. `POST /api/v1/auth/login` → token Sanctum.
3. Requests siguientes: `Authorization: Bearer {token}`.
4. Tokens almacenados en **BD tenant** (`personal_access_tokens`).

CORS: permitir origen del front por subdominio tenant.

---

## 7. Provisionar tenant (v1)

Comando objetivo (implementar en fase 1):

```bash
php artisan tenant:provision personal \
  --name="Personal" \
  --domain=personal.localhost
```

Pasos internos:

1. Crear registro `Tenant` + `Domain` (Stancl).
2. Ejecutar pipeline Stancl (crear BD, migrar `database/migrations/tenant`).
3. Seed: usuario owner, `settings.tax_enabled=false`, moneda `COP`.
4. Imprimir credenciales de desarrollo (solo local).

---

## 8. Migraciones

```txt
database/migrations/           # central: tenants, domains
database/migrations/tenant/  # negocio por tenant
```

Comandos:

```bash
php artisan migrate                    # central
php artisan tenants:migrate            # todos los tenants
php artisan tenants:migrate --tenants=uuid  # uno
```

---

## 9. Modelos Eloquent

- **Landlord:** `App\Models\Tenant` (extends Stancl tenant model).
- **Negocio:** `App\Models\Tenant\Client`, etc. — conexión tenant automática vía Stancl.

Regla: **nunca** consultar modelos tenant desde rutas central sin `$tenant->run()`.

---

## 10. Cache, storage, colas

### Cache (Redis obligatorio en dev/prod)

| Contexto           | Driver              | Aislamiento                                                          |
| ------------------ | ------------------- | -------------------------------------------------------------------- |
| Central (landlord) | `CACHE_STORE=redis` | Prefijo `CACHE_PREFIX` (ej. `freelance-central-cache-`)              |
| Tenant             | Mismo Redis         | **Tags** Stancl: `tenant` + `tenant_id` (`CacheTenancyBootstrapper`) |

**No usar** `CACHE_STORE=database` con multi-tenant: el driver `database` no soporta tags y Spatie Permission intenta vaciar cache en migraciones tenant.

La tabla `cache` en BD central y en migraciones tenant es **respaldo** si alguien fuerza `database` en local; el estándar del proyecto es Redis.

`RedisTenancyBootstrapper` solo hace falta si llamas `Redis::` directo (colas, locks custom). Para `Cache::` basta `CacheTenancyBootstrapper` + phpredis.

```bash
# Redis local (o docker compose -f docker-compose.dev.yml up -d redis)
redis-cli ping   # PONG

# .env api
CACHE_STORE=redis
REDIS_CLIENT=phpredis
```

### Storage y colas

- PDFs y plantillas: disco `tenant` → `storage/app/tenants/{id}/...`
- Colas: v1 `QUEUE_CONNECTION=database`; fase siguiente `redis` + `QueueTenancyBootstrapper`
- Jobs (email, PDF): implementar `TenantAware` / inicializar tenancy en el job.

---

## 11. Testing

| Tipo           | Enfoque                                                                       |
| -------------- | ----------------------------------------------------------------------------- |
| Unit           | Sin BD o SQLite si aplica                                                     |
| Feature tenant | Crear tenant en central + `tenants:migrate` + test dentro de `$tenant->run()` |
| Central        | Tests sin middleware tenancy                                                  |

BD de test: sufijo `_test` (ej. `freelance_central_test`, `freelance_tenant_personal_test`).

---

## 12. Futuro: nuevo tenant “empresa”

Al constituir empresa:

```bash
php artisan tenant:provision acme-sas --name="ACME SAS" --domain=acme.tudominio.com
```

Activar `tax_enabled` en settings del tenant empresa sin afectar tenant `personal`.

---

## 13. Anti-patrones

- Lógica de negocio en bootstrappers Stancl.
- Rutas tenant accesibles desde dominio central sin tenancy.
- Queries a `clients` desde comando central sin `run()`.
- Cache keys sin prefijo tenant en código de aplicación.
