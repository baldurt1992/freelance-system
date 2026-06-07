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
    :ui="{ root: 'bg-default/85 ring ring-default/80 backdrop-blur-sm' }"
  >
    <div class="space-y-3">
      <NuxtLink
        v-for="item in items"
        :key="item.label"
        :to="item.to"
        class="flex items-center justify-between gap-4 rounded-xl border border-default/80 bg-default/70 px-4 py-3 transition-[background-color,border-color,transform,box-shadow] duration-300 ease-out hover:border-primary/20 hover:bg-primary/6 hover:-translate-y-px hover:shadow-[0_14px_30px_-26px_color-mix(in_srgb,var(--ui-color-primary-950)_24%,transparent)]"
      >
        <div class="flex min-w-0 items-center gap-3">
          <div class="rounded-2xl bg-primary/10 p-2 text-primary ring ring-inset ring-primary/15">
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

        <UBadge :label="String(item.value)" color="primary" variant="subtle" class="rounded-full" />
      </NuxtLink>
    </div>
  </UPageCard>
</template>
