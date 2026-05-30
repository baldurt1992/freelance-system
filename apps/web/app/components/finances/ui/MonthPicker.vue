<script setup lang="ts">
import {
  buildFinanceMonthSelectItems,
  buildFinanceYearSelectItems,
  defaultFinanceMonth,
  formatFinanceMonth,
  normalizeFinanceMonth,
  parseFinanceMonth,
} from "~/composables/finances/financeMonthHelpers";

const props = defineProps<{
  month: string;
  monthFallback: string;
}>();

const emit = defineEmits<{
  "update:month": [value: string];
}>();

const currentMonth = parseFinanceMonth(defaultFinanceMonth())!;
const yearItems = buildFinanceYearSelectItems({ yearsAfter: 0 });

function clampToCurrentMonth(value: { year: number; month: number }) {
  if (value.year > currentMonth.year) return currentMonth;
  if (value.year === currentMonth.year && value.month > currentMonth.month) return currentMonth;
  return value;
}

const monthItems = computed(() => {
  const items = buildFinanceMonthSelectItems();

  if (selected.value.year < currentMonth.year) {
    return items;
  }

  return items.filter((item) => item.value <= currentMonth.month);
});

const selected = ref(clampToCurrentMonth(normalizeFinanceMonth(props.month, props.monthFallback)));

watch(
  () => props.month,
  (value) => {
    selected.value = clampToCurrentMonth(normalizeFinanceMonth(value, props.monthFallback));
  },
);

function emitMonth(year: number, month: number) {
  emit("update:month", formatFinanceMonth(year, month));
}

function onYearChange(year: number | undefined) {
  if (year === undefined) return;
  selected.value = clampToCurrentMonth({ ...selected.value, year });
  emitMonth(selected.value.year, selected.value.month);
}

function onMonthChange(month: number | undefined) {
  if (month === undefined) return;
  selected.value = clampToCurrentMonth({ ...selected.value, month });
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
