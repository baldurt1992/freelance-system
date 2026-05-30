<script setup lang="ts">
import type { NavigationMenuItem } from '@nuxt/ui'

const open = ref(false)

const links = [[{
  label: 'Inicio',
  icon: 'i-lucide-house',
  to: '/',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Clientes',
  icon: 'i-lucide-users',
  to: '/clients',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Cotizaciones',
  icon: 'i-lucide-file-text',
  to: '/quotes',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Proyectos',
  icon: 'i-lucide-briefcase',
  to: '/projects',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Finanzas',
  icon: 'i-lucide-wallet',
  to: '/finances',
  onSelect: () => {
    open.value = false
  }
}], [{
  label: 'Configuración',
  icon: 'i-lucide-settings',
  to: '/settings',
  onSelect: () => {
    open.value = false
  }
}]] satisfies NavigationMenuItem[][]

const groups = computed(() => [{
  id: 'links',
  label: 'Ir a',
  items: links.flat()
}])

</script>

<template>
  <UDashboardGroup unit="rem">
    <UDashboardSidebar
      id="default"
      v-model:open="open"
      collapsible
      resizable
      class="bg-elevated/25"
      :ui="{ footer: 'lg:border-t lg:border-default' }"
    >
      <template #header="{ collapsed }">
        <TeamsMenu :collapsed="collapsed" />
      </template>

      <template #default="{ collapsed }">
        <UDashboardSearchButton :collapsed="collapsed" class="bg-transparent ring-default" />

        <UNavigationMenu
          :collapsed="collapsed"
          :items="links[0]"
          orientation="vertical"
          tooltip
          popover
        />

        <UNavigationMenu
          :collapsed="collapsed"
          :items="links[1]"
          orientation="vertical"
          tooltip
          class="mt-auto"
        />
      </template>

      <template #footer="{ collapsed }">
        <UserMenu :collapsed="collapsed" />
      </template>
    </UDashboardSidebar>

    <UDashboardSearch :groups="groups" />

    <slot />
  </UDashboardGroup>
</template>
