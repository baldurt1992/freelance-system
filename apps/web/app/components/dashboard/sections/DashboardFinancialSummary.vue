<script setup lang="ts">
import type { DashboardFinancialSummary } from "@freelance/contracts";
import { useFinanceSummary } from "~/composables/finances/useFinanceSummary";
import { formatMoney } from "~/utils/formatMoney";

const props = defineProps<{
  summary: DashboardFinancialSummary;
  currency: string;
  month: string;
}>();

const { getNetLabelText } = useFinanceSummary();
</script>

<template>
  <UPageCard
    title="Resumen financiero"
    :description="`Corte del mes ${month}.`"
    variant="subtle"
  >
    <div class="grid gap-4 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)] lg:items-start">
      <div class="space-y-2">
        <p class="text-sm text-muted">Balance neto</p>
        <p class="text-3xl font-semibold text-highlighted">
          {{ formatMoney(summary.net_cents, currency) }}
        </p>
        <UBadge
          :label="getNetLabelText(summary.label)"
          :color="summary.net_cents > 0 ? 'success' : summary.net_cents < 0 ? 'error' : 'neutral'"
          variant="subtle"
        />
      </div>

      <div class="grid gap-3">
        <div class="rounded-lg border border-default p-4">
          <p class="text-sm text-muted">Ingresos</p>
          <p class="mt-1 text-lg font-semibold text-success">
            {{ formatMoney(summary.income_cents, currency) }}
          </p>
        </div>
        <div class="rounded-lg border border-default p-4">
          <p class="text-sm text-muted">Gastos</p>
          <p class="mt-1 text-lg font-semibold text-error">
            {{ formatMoney(summary.expense_cents, currency) }}
          </p>
        </div>
      </div>
    </div>
  </UPageCard>
</template>
