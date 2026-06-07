<script setup lang="ts">
import type { DashboardKpis } from "@freelance/contracts";
import type { PageCardProps } from "@nuxt/ui";
import { formatMoney } from "~/utils/formatMoney";

const props = defineProps<{
  kpis: DashboardKpis;
  currency: string;
}>();

type KpiColor = NonNullable<PageCardProps["highlightColor"]>;

const items = computed(() => [
  {
    label: "Por cobrar actual",
    value: formatMoney(props.kpis.receivables_cents, props.currency),
    icon: "i-lucide-badge-dollar-sign",
    color: "warning" as KpiColor,
    detail: "Saldo pendiente con corte al estado actual",
  },
  {
    label: "Ingresos del mes",
    value: formatMoney(props.kpis.income_cents, props.currency),
    icon: "i-lucide-trending-up",
    color: "success" as KpiColor,
  },
  {
    label: "Gastos del mes",
    value: formatMoney(props.kpis.expense_cents, props.currency),
    icon: "i-lucide-trending-down",
    color: "error" as KpiColor,
  },
  {
    label: "Cotizaciones pendientes",
    value: String(props.kpis.pending_quotes_count),
    icon: "i-lucide-file-clock",
    color: "primary" as KpiColor,
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
      :highlight-color="item.color"
      :ui="{
        container: 'gap-y-2',
        wrapper: 'items-start',
        root: 'bg-default/85 ring ring-default/80 backdrop-blur-sm transition-[transform,box-shadow,border-color,background-color] duration-300 ease-out hover:-translate-y-0.5 hover:shadow-[0_18px_40px_-28px_color-mix(in_srgb,var(--ui-color-primary-950)_26%,transparent)]',
        leading: 'rounded-2xl p-2.5 ring ring-inset ring-default/45 shadow-sm',
        leadingIcon: item.color === 'success'
          ? 'text-success'
          : item.color === 'error'
            ? 'text-error'
            : item.color === 'warning'
              ? 'text-warning'
              : 'text-primary',
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
