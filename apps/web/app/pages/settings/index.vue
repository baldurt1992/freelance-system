<script setup lang="ts">
const tenant = useTenantStore();
const auth = useAuthStore();

const settingsLinks = [
  {
    label: "Facturación e IVA",
    description: "Activa o desactiva el IVA en cotizaciones nuevas y borradores.",
    to: "/settings/billing",
    icon: "i-lucide-receipt",
  },
  {
    label: "Plantillas de documentos",
    description: "Personaliza HTML para cotizaciones y cuentas de cobro.",
    to: "/settings/templates",
    icon: "i-lucide-file-code",
  },
  {
    label: "Seguridad",
    description: "Actualiza tu contraseña y revisa acciones sensibles de la cuenta.",
    to: "/settings/security",
    icon: "i-lucide-shield-check",
  },
] as const;
</script>

<template>
  <div class="space-y-6">
    <UPageCard
      title="General"
      description="Resumen del workspace y accesos a la configuración activa del producto."
      variant="naked"
      orientation="horizontal"
    />

    <UPageCard
      title="Workspace"
      variant="subtle"
    >
      <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <dt class="text-sm text-muted">Nombre</dt>
          <dd class="font-medium">{{ tenant.displayName }}</dd>
        </div>
        <div>
          <dt class="text-sm text-muted">Moneda</dt>
          <dd>{{ tenant.currency }}</dd>
        </div>
        <div>
          <dt class="text-sm text-muted">IVA</dt>
          <dd>{{ tenant.taxEnabled ? `Activo (${tenant.taxRate}%)` : "Desactivado" }}</dd>
        </div>
        <div v-if="auth.user">
          <dt class="text-sm text-muted">Usuario en sesión</dt>
          <dd>{{ auth.user.name }}</dd>
          <dd class="text-sm text-muted">{{ auth.user.email }}</dd>
        </div>
      </dl>
    </UPageCard>

    <UPageCard
      title="Configuración disponible"
      variant="subtle"
    >
      <div class="space-y-3">
        <div
          v-for="link in settingsLinks"
          :key="link.to"
          class="flex flex-col gap-3 rounded-lg border border-default p-4 sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="flex items-start gap-3">
            <UIcon :name="link.icon" class="mt-0.5 size-5 text-muted" />
            <div>
              <p class="font-medium">{{ link.label }}</p>
              <p class="text-sm text-muted">{{ link.description }}</p>
            </div>
          </div>
          <UButton
            :to="link.to"
            label="Abrir"
            variant="outline"
            class="w-fit"
          />
        </div>
      </div>
    </UPageCard>
  </div>
</template>
