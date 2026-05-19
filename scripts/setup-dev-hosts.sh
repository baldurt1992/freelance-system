#!/usr/bin/env bash
# Añade personal.localhost para desarrollo (Stancl tenancy por dominio).
set -euo pipefail

ENTRY="127.0.0.1 personal.localhost"
MARKER="# freelance-system"

add_hosts_block() {
  local file="$1"
  if grep -qE '[[:space:]]personal\.localhost([[:space:]]|$)' "$file" 2>/dev/null; then
    echo "OK: personal.localhost ya está en $file"
    return 0
  fi
  echo "Añadiendo a $file (requiere permisos de administrador)..."
  printf '\n%s\n%s\n' "$MARKER" "$ENTRY" | sudo tee -a "$file" > /dev/null
  echo "Listo: $file"
}

echo "=== Freelance System — hosts de desarrollo ==="
echo ""

add_hosts_block /etc/hosts

echo ""
echo "Si usas el navegador en WINDOWS (Chrome/Edge fuera de WSL), también agrega"
echo "la misma línea en (como Administrador):"
echo "  C:\\Windows\\System32\\drivers\\etc\\hosts"
echo ""
echo "  $ENTRY"
echo ""
echo "Luego abre:"
echo "  Web:  http://personal.localhost:3000/login"
echo "  API:  http://personal.localhost:8000/api/v1/central/health"
echo ""
echo "  (Laravel por defecto: php artisan serve → puerto 8000)"
echo ""
echo "Comprueba resolución:"
getent hosts personal.localhost 2>/dev/null || ping -c1 personal.localhost 2>/dev/null || true
