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
      class="bg-transparent"
      :ui="{ footer: 'lg:border-t lg:border-default/80' }"
    >
      <template #header="{ collapsed }">
        <TeamsMenu :collapsed="collapsed" />
      </template>

      <template #default="{ collapsed }">
        <UDashboardSearchButton
          :collapsed="collapsed"
          class="bg-default/70 ring-default/80 backdrop-blur-sm hover:bg-default"
        />

        <UNavigationMenu
          :collapsed="collapsed"
          :items="links[0]"
          color="primary"
          highlight
          orientation="vertical"
          tooltip
          popover
          class="[&_[data-state=open]]:text-primary [&_[data-state=open]]:before:bg-primary/10 [&_[data-state=open]]:shadow-[inset_0_0_0_1px_color-mix(in_srgb,var(--ui-primary)_18%,transparent)]"
        />

        <UNavigationMenu
          :collapsed="collapsed"
          :items="links[1]"
          color="primary"
          highlight
          orientation="vertical"
          tooltip
          class="mt-auto [&_[data-state=open]]:text-primary [&_[data-state=open]]:before:bg-primary/10 [&_[data-state=open]]:shadow-[inset_0_0_0_1px_color-mix(in_srgb,var(--ui-primary)_18%,transparent)]"
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
