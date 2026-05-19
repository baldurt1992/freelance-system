# Onboarding — Freelance System

Guía para levantar el monorepo en local. Fases 0–2 del [plan de bootstrap](../plans/bootstrap.md) ya aplicadas.

## Requisitos

| Herramienta | Versión                                              |
| ----------- | ---------------------------------------------------- |
| PHP         | 8.3+ (`pdo_mysql`, `mbstring`, `xml`, `curl`, `zip`) |
| Composer    | 2.6+                                                 |
| Node.js     | **22.13+**                                           |
| pnpm        | **11+**                                              |
| MySQL       | 8+                                                   |
| Redis       | 7+ (cache multi-tenant)                              |

Opcional: Docker + Docker Compose (`docker-compose.dev.yml` para MySQL/Redis).

## Variables de entorno

| Archivo                                              | Uso                                       |
| ---------------------------------------------------- | ----------------------------------------- |
| [.env.example](../../.env.example)                   | Índice del monorepo (sin runtime en raíz) |
| [api/.env.example](../../api/.env.example)           | Laravel central + Stancl                  |
| [apps/web/.env.example](../../apps/web/.env.example) | Nuxt (`NUXT_PUBLIC_API_BASE_URL`)         |

```bash
cp api/.env.example api/.env
cp apps/web/.env.example apps/web/.env
# api: php artisan key:generate
```

## Hosts (obligatorio)

Stancl identifica el tenant por **dominio**, no por `localhost`.

### WSL / Linux

```bash
bash scripts/setup-dev-hosts.sh
# o: echo '127.0.0.1 personal.localhost' | sudo tee -a /etc/hosts
getent hosts personal.localhost   # → 127.0.0.1
```

### Windows (navegador fuera de WSL)

PowerShell **como Administrador**:

```powershell
.\scripts\setup-dev-hosts.ps1
```

O en `C:\Windows\System32\drivers\etc\hosts`:

```txt
127.0.0.1 personal.localhost
```

## Setup local (recomendado)

### 1. MySQL — BD central + usuario app

```bash
mysql -u TU_USUARIO -p < api/database/scripts/dev-mysql-bootstrap.sql
```

Ajusta credenciales en `api/.env` (`DB_*`). Por defecto del script: usuario `freelance` / BD `freelance_central`.

### 2. Redis

```bash
redis-cli ping   # PONG
```

O solo servicios Docker:

```bash
docker compose -f docker-compose.dev.yml up -d redis
```

### 3. API (Laravel)

```bash
cd api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate

php artisan tenant:provision personal \
  --name="Personal" \
  --domain=personal.localhost \
  --email=admin@admin.com \
  --password=TU_PASSWORD_DEV

php artisan serve --host=0.0.0.0 --port=8000
```

### 4. Web (Nuxt) — desde la raíz del monorepo

```bash
# Node 22+ (ej. nvm use 22)
pnpm install
pnpm dev:web
```

Build scripts: allowlist en `pnpm-workspace.yaml` → `allowBuilds` (ver [apps/web/README.md](../../apps/web/README.md)).

## URLs de desarrollo

| Servicio           | URL                                         |
| ------------------ | ------------------------------------------- |
| **Login (Nuxt)**   | http://personal.localhost:3000/login        |
| **Dashboard**      | http://personal.localhost:3000/             |
| **API tenant**     | http://personal.localhost:8000/api/v1       |
| **Health central** | http://localhost:8000/api/v1/central/health |
| MySQL              | `127.0.0.1:3306`                            |
| Redis              | `127.0.0.1:6379`                            |

Importante:

- Abre el front en **`personal.localhost`**, no en `localhost:3000`.
- La API tenant debe usar el **mismo host** (`personal.localhost`) y puerto **8000** (default de `artisan serve`).
- `apps/web/.env` → `NUXT_PUBLIC_API_BASE_URL=http://personal.localhost:8000/api/v1`

## Convenciones rápidas

| Área   | Convención                                       |
| ------ | ------------------------------------------------ |
| API    | REST `/api/v1`, JSON                             |
| Auth   | `Authorization: Bearer {token}`                  |
| Tenant | Dominio `personal.localhost` (dev)               |
| Dinero | Campos `*_cents` (enteros)                       |
| IVA    | `tax_enabled` en tenant `data` (default `false`) |

## Estructura mental

1. [ARCHITECTURE.md](./ARCHITECTURE.md)
2. [TENANCY.md](./TENANCY.md) — antes de rutas o modelos tenant
3. [AGENTS.md](../../AGENTS.md) — agentes de código
4. Contratos API → [packages/contracts/README.md](../../packages/contracts/README.md)

## Tests

```bash
cd api && php artisan test

cd apps/web && pnpm typecheck
```

BD de test: sufijo `_test` únicamente.

## Siguiente paso

**Fase 4 — Clients:** contrato Zod → migración tenant → API → composables → UI CRUD.
