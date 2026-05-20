# Flujo Git — todas las fases

Ejecutar **al inicio y al cierre** de cada plan de fase. No saltar pasos.

---

## A. Antes de empezar una fase

1. Asegurar working tree limpio: `git status` (sin cambios sin commitear salvo la fase actual).
2. Actualizar `main` local:
   ```bash
   git checkout main
   git pull origin main
   ```
3. Crear rama desde `main`:
   ```bash
   git checkout -b feature/<nombre-fase>
   ```
   Usar el nombre exacto del plan (ej. `feature/clients-fase-4`).
4. Push temprano (backup + PR):
   ```bash
   git push -u origin feature/<nombre-fase>
   ```

---

## B. Durante la fase

1. **Una fase = una rama.** No mezclar Clientes + Quotes en la misma rama.
2. Commits pequeños y descriptivos (ej. `feat(clients): add tenant migration`).
3. No commitear: `.env`, secretos, `node_modules/`, `vendor/`.
4. Leer antes de codificar (cada plan lista archivos obligatorios).

---

## C. Verificación obligatoria (antes de PR)

Ejecutar **todos** los comandos que el plan marque como “Verificación”. Típicamente:

```bash
# API (desde api/)
php artisan test

# Web (desde raíz o apps/web)
pnpm typecheck:web
```

Si un comando falla, **no abrir PR** hasta corregir.

---

## D. Cierre de fase — PR y merge

1. Commit final de lo pendiente en la rama feature.
2. Push:
   ```bash
   git push origin feature/<nombre-fase>
   ```
3. Crear PR hacia `main`:

   ```bash
   gh pr create --base main --head feature/<nombre-fase> \
     --title "feat: <título fase>" \
     --body "$(cat <<'EOF'
   ## Fase
   <número y nombre>

   ## Checklist
   - [ ] Tests API pasan
   - [ ] typecheck web pasa (si aplica UI)
   - [ ] Validación manual según plan

   EOF
   )"
   ```

4. Revisar diff; marcar checklist del plan como completado en el PR.
5. Tras aprobación: merge a `main` (squash o merge commit según preferencia del repo).
6. Local post-merge:
   ```bash
   git checkout main
   git pull origin main
   git branch -d feature/<nombre-fase>
   git push origin --delete feature/<nombre-fase>
   ```
7. **Siguiente fase:** solo arrancar desde `main` actualizado (paso A).

---

## E. Reglas para agentes

| Regla                            | Acción                                                |
| -------------------------------- | ----------------------------------------------------- |
| Fase anterior no mergeada        | Detenerse; no crear rama nueva                        |
| Tests fallan                     | Arreglar en la misma rama                             |
| Cambio fuera de alcance del plan | No incluir; otro PR                                   |
| UI “bonita” fuera de alcance     | Funcional primero; pulido UI es fase posterior global |

---

## F. Remoto

- **Publicar ramas:** sí (`git push -u origin`).
- **Trabajar solo local:** solo para experimentos descartables; features que van a `main` siempre con push + PR.
