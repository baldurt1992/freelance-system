<script setup lang="ts">
import type { DashboardKpis } from "@freelance/contracts";
import { formatMoney } from "~/utils/formatMoney";

const props = defineProps<{
  kpis: DashboardKpis;
  currency: string;
}>();

const items = computed(() => [
  {
    label: "Por cobrar actual",
    value: formatMoney(props.kpis.receivables_cents, props.currency),
    icon: "i-lucide-badge-dollar-sign",
    detail: "Saldo pendiente con corte al estado actual",
  },
  {
    label: "Ingresos del mes",
    value: formatMoney(props.kpis.income_cents, props.currency),
    icon: "i-lucide-trending-up",
  },
  {
    label: "Gastos del mes",
    value: formatMoney(props.kpis.expense_cents, props.currency),
    icon: "i-lucide-trending-down",
  },
  {
    label: "Cotizaciones pendientes",
    value: String(props.kpis.pending_quotes_count),
    icon: "i-lucide-file-clock",
    detail: `${props.kpis.active_projects_count} proyecto(s) activo(s)`,
  },
]);
</script>

<template>
  <UPageGrid class="gap-4 sm:gap-6 xl:grid-cols-4">
    <UPageCard
      v-for="item in items"
      :key="item.label"
      :title="item.label"
      :icon="item.icon"
      variant="subtle"
      :ui="{
        container: 'gap-y-2',
        wrapper: 'items-start',
        leading: 'rounded-full bg-primary/10 p-2.5 ring ring-inset ring-primary/15',
        title: 'text-xs font-normal uppercase tracking-[0.12em] text-muted'
      }"
    >
      <div class="space-y-1">
        <p class="text-2xl font-semibold text-highlighted">
          {{ item.value }}
        </p>
        <p v-if="item.detail" class="text-sm text-muted">
          {{ item.detail }}
        </p>
      </div>
    </UPageCard>
  </UPageGrid>
</template>
