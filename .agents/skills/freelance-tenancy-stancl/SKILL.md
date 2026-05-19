---
name: freelance-tenancy-stancl
description: Use for Stancl tenancy setup, central vs tenant routes, tenant provision, tenants:migrate, tenant-aware jobs, and MySQL database-per-tenant in Freelance System.
---

# Freelance Tenancy (Stancl)

## When to use

- `routes/tenant.php` vs central routes
- Middleware `InitializeTenancyBy*`
- `php artisan tenant:provision`
- `tenants:migrate`, `tenants:run`
- Tests inside `$tenant->run()`
- Storage/cache per tenant

## Rules

1. **Do not reimplement** Stancl bootstrappers; extend via config and thin wrappers only.
2. **Central routes** must not touch tenant business models without `$tenant->run()`.
3. **Production tenant ID:** subdomain → `domains` table (see `docs/main/TENANCY.md`).
4. **Auth tokens** live in tenant DB (v1).
5. **MySQL:** one database per tenant; central holds `tenants`, `domains`.
6. **Jobs** that write tenant data must initialize tenancy.

## Provision (target command)

```bash
php artisan tenant:provision {slug} --name= --domain=
```

No Plesk/SSL automation in this project.

## Anti-patterns

- Business logic in `TenancyServiceProvider` bootstrappers.
- Global queries to `clients` without tenant context.
- Shared cache keys without tenant prefix.

## References

- `docs/main/TENANCY.md`
- `docs/main/ARCHITECTURE.md` §5–6
