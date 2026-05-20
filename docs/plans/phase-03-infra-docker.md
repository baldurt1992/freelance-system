# Fase 3 (opcional) — Docker api + web

**Rama:** `feature/infra-docker-fase-3`  
**Prerrequisito:** Puede ejecutarse en paralelo solo si **no** toca código de dominio; ideal entre fases o cuando necesites contenedores.

**Nota:** No bloquea fases 4–9. Prioridad baja frente a Clientes.

---

## Leer primero

1. [WORKFLOW.md](./WORKFLOW.md)
2. [../main/ONBOARDING.md](../main/ONBOARDING.md)
3. `docker-compose.dev.yml` actual

---

## Paso 0 — Git

```bash
git checkout main && git pull origin main
git checkout -b feature/infra-docker-fase-3
git push -u origin feature/infra-docker-fase-3
```

---

## Paso 1 — Servicio API

1. `docker/api/Dockerfile` — PHP 8.x, extensions: pdo_mysql, redis, intl
2. Servicio `api` en compose: mount `api/`, puerto 8000, env file `api/.env`
3. Entrypoint: `php artisan serve` o php-fpm + nginx sidecar (elegir uno, documentar)

---

## Paso 2 — Servicio Web

1. `docker/web/Dockerfile` — Node 22, pnpm
2. Servicio `web`: dev `pnpm dev:web` puerto 3000
3. `NUXT_PUBLIC_API_BASE_URL` apuntando a host `api` dentro de red compose

---

## Paso 3 — Hosts / docs

1. Actualizar ONBOARDING con perfil “todo en Docker” vs “híbrido” (actual).
2. Script opcional `scripts/dev-up.sh` → `docker compose up -d`

---

## Paso 4 — Verificación

```bash
docker compose -f docker-compose.dev.yml up -d
curl http://personal.localhost:8000/api/v1/central/health
# login front según ONBOARDING
```

---

## Paso 5 — Cierre PR

Título: `chore: docker compose for api and web dev`

---

## Definition of Done

- [ ] `docker compose up` levanta api + web + redis (+ mysql si aplica)
- [ ] ONBOARDING actualizado
- [ ] Merge a `main`

---

## No hacer

- Cambiar lógica de negocio
- Producción/K8s en esta fase
