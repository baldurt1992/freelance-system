<script setup lang="ts">
import {
  buildFinanceMonthSelectItems,
  buildFinanceYearSelectItems,
  formatFinanceMonth,
  normalizeFinanceMonth,
} from "~/composables/finances/financeMonthHelpers";

const props = defineProps<{
  month: string;
  monthFallback: string;
}>();

const emit = defineEmits<{
  "update:month": [value: string];
}>();

const yearItems = buildFinanceYearSelectItems();
const monthItems = buildFinanceMonthSelectItems();

const selected = ref(normalizeFinanceMonth(props.month, props.monthFallback));

watch(
  () => props.month,
  (value) => {
    selected.value = normalizeFinanceMonth(value, props.monthFallback);
  },
);

function emitMonth(year: number, month: number) {
  emit("update:month", formatFinanceMonth(year, month));
}

function onYearChange(year: number | undefined) {
  if (year === undefined) return;
  selected.value = { ...selected.value, year };
  emitMonth(selected.value.year, selected.value.month);
}

function onMonthChange(month: number | undefined) {
  if (month === undefined) return;
  selected.value = { ...selected.value, month };
  emitMonth(selected.value.year, selected.value.month);
}
</script>

<template>
  <div
    class="flex min-w-52 items-center gap-2"
    role="group"
    aria-label="Mes del reporte"
  >
    <USelect
      id="finance-month-year"
      name="finance_month_year"
      :model-value="selected.year"
      :items="yearItems"
      icon="i-lucide-calendar"
      class="w-28"
      @update:model-value="onYearChange($event as number | undefined)"
    />
    <USelect
      id="finance-month-month"
      name="finance_month_month"
      :model-value="selected.month"
      :items="monthItems"
      class="min-w-36 flex-1"
      @update:model-value="onMonthChange($event as number | undefined)"
    />
  </div>
</template>
