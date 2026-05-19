<script setup lang="ts">
import type { DropdownMenuItem } from '@nuxt/ui'

defineProps<{
  collapsed?: boolean
}>()

const tenant = useTenantStore()

const team = computed(() => ({
  label: tenant.displayName,
  avatar: {
    alt: tenant.displayName
  }
}))

const items = computed<DropdownMenuItem[][]>(() => [[{
  type: 'label',
  label: team.value.label,
  avatar: team.value.avatar,
  description: tenant.currency
}]])
</script>

<template>
  <UDropdownMenu
    :items="items"
    :content="{ align: 'start', collisionPadding: 12 }"
    :ui="{ content: collapsed ? 'w-40' : 'w-(--reka-dropdown-menu-trigger-width)' }"
  >
    <UButton
      v-bind="{
        ...team,
        label: collapsed ? undefined : team.label,
        trailingIcon: collapsed ? undefined : 'i-lucide-chevrons-up-down'
      }"
      color="neutral"
      variant="ghost"
      block
      :square="collapsed"
      class="data-[state=open]:bg-elevated"
      :ui="{
        trailingIcon: 'text-dimmed'
      }"
    />
  </UDropdownMenu>
</template>
