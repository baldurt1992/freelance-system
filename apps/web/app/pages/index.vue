<script setup lang="ts">
const sections = [
  {
    title: 'Clientes',
    description: 'Consulta tu base de clientes y actualiza sus datos.',
    to: '/clients',
    icon: 'i-lucide-users'
  },
  {
    title: 'Cotizaciones',
    description: 'Revisa borradores, cotizaciones enviadas y conversiones a proyecto.',
    to: '/quotes',
    icon: 'i-lucide-file-text'
  },
  {
    title: 'Proyectos',
    description: 'Haz seguimiento a proyectos activos, cobros y cierres.',
    to: '/projects',
    icon: 'i-lucide-briefcase'
  },
  {
    title: 'Finanzas',
    description: 'Consulta ingresos, gastos y movimientos del periodo.',
    to: '/finances',
    icon: 'i-lucide-wallet'
  },
  {
    title: 'Configuración',
    description: 'Ajusta IVA, plantillas y seguridad del workspace.',
    to: '/settings',
    icon: 'i-lucide-settings'
  }
] as const
</script>

<template>
  <UDashboardPanel id="home">
    <template #header>
      <UDashboardNavbar title="Inicio">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="space-y-6">
        <UPageCard
          title="Dashboard en preparación"
          description="Quitamos el contenido placeholder del template para implementar en una rama aparte un resumen operativo real del negocio."
          variant="naked"
          orientation="horizontal"
        />

        <UAlert
          color="info"
          variant="subtle"
          icon="i-lucide-layout-dashboard"
          title="Sin métricas inventadas"
          description="Mientras definimos el dashboard operativo, usa estos accesos para navegar por los módulos activos del sistema."
        />

        <UPageGrid class="gap-4 sm:gap-6 lg:grid-cols-2">
          <UPageCard
            v-for="section in sections"
            :key="section.to"
            :title="section.title"
            :description="section.description"
            :icon="section.icon"
            :to="section.to"
            variant="subtle"
          >
            <template #footer>
              <UButton
                :to="section.to"
                label="Abrir módulo"
                variant="outline"
                class="w-fit"
              />
            </template>
          </UPageCard>
        </UPageGrid>
      </div>
    </template>
  </UDashboardPanel>
</template>
