<script setup lang="ts">
import type { DashboardPending } from "@freelance/contracts";

const props = defineProps<{
  pending: DashboardPending;
}>();

const items = computed(() => [
  {
    label: "Proyectos con saldo pendiente",
    value: props.pending.projects_with_balance_count,
    to: "/projects",
    icon: "i-lucide-briefcase-business",
  },
  {
    label: "Cotizaciones enviadas por revisar",
    value: props.pending.sent_quotes_count,
    to: "/quotes",
    icon: "i-lucide-send",
  },
  {
    label: "Cotizaciones en borrador",
    value: props.pending.draft_quotes_count,
    to: "/quotes",
    icon: "i-lucide-file-pen-line",
  },
]);
</script>

<template>
  <UPageCard
    title="Pendientes"
    description="Acciones que todavía requieren seguimiento en el workspace."
    variant="subtle"
  >
    <div class="space-y-3">
      <NuxtLink
        v-for="item in items"
        :key="item.label"
        :to="item.to"
        class="flex items-center justify-between gap-4 rounded-lg border border-default px-4 py-3 transition-colors hover:bg-elevated/50"
      >
        <div class="flex min-w-0 items-center gap-3">
          <div class="rounded-full bg-primary/10 p-2 text-primary ring ring-inset ring-primary/15">
            <UIcon :name="item.icon" class="size-4" />
          </div>
          <div class="min-w-0">
            <p class="font-medium text-highlighted">
              {{ item.label }}
            </p>
            <p class="text-sm text-muted">
              Ir al módulo correspondiente
            </p>
          </div>
        </div>

        <UBadge :label="String(item.value)" color="neutral" variant="subtle" />
      </NuxtLink>
    </div>
  </UPageCard>
</template>
