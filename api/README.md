# Freelance System API

Laravel 13 + Stancl Tenancy + Sanctum (Bearer) + Spatie Permission.

Setup completo del monorepo: **[docs/main/ONBOARDING.md](../docs/main/ONBOARDING.md)**.

## Solo esta app

```bash
cd api
composer install
cp .env.example .env
php artisan key:generate

# BD central (ajusta usuario MySQL en el script si aplica)
mysql -u TU_USUARIO -p < database/scripts/dev-mysql-bootstrap.sql

php artisan migrate

php artisan tenant:provision personal \
  --name="Personal" \
  --domain=personal.localhost \
  --email=admin@admin.com \
  --password=TU_PASSWORD

php artisan serve --host=0.0.0.0 --port=8000
```

## Rutas

| Contexto | Ejemplo                                                 |
| -------- | ------------------------------------------------------- |
| Central  | `GET http://localhost:8000/api/v1/central/health`       |
| Tenant   | `POST http://personal.localhost:8000/api/v1/auth/login` |

El tenant se resuelve por **dominio** (`personal.localhost`), no por path.

## Config notable (`.env`)

| Tema     | Valor recomendado                               |
| -------- | ----------------------------------------------- |
| Sesiones | `SESSION_DRIVER=file` (SPA + Bearer)            |
| Cache    | `CACHE_STORE=redis` + Redis en `127.0.0.1:6379` |

## Tests

```bash
php artisan test
```

SQLite en memoria (central + tenants).

## Comandos útiles

```bash
php artisan tenants:list
php artisan tenants:migrate
```
