<script setup lang="ts">
  import type { QuoteLineForm } from "~/composables/quotes/useQuoteLines";
  import { formatMoney } from "~/utils/formatMoney";

  const props = defineProps<{
    line: QuoteLineForm;
    index: number;
  }>();

  const isIncomplete = computed(
    () => !props.line.description || !props.line.unit_amount || props.line.unit_amount <= 0,
  );

  const conceptValueCents = computed(() => {
    return Math.round(props.line.quantity * (props.line.unit_amount ?? 0) * 100);
  });
</script>

<template>
  <div class="flex w-full items-center justify-between gap-3">
    <div class="flex min-w-0 items-center gap-2">
      <span class="shrink-0 text-sm font-semibold text-highlighted">Concepto {{ index + 1 }}</span>
      <UBadge
        v-if="isIncomplete"
        label="Incompleto"
        color="warning"
        variant="subtle"
        size="xs"
        class="shrink-0"
      />
      <span v-else class="truncate text-sm text-muted">
        {{ line.description }}
      </span>
    </div>

    <div class="flex shrink-0 items-center gap-3">
      <span class="text-sm text-muted">Cant. {{ line.quantity }}</span>
      <span class="text-sm font-semibold text-highlighted">
        {{ formatMoney(conceptValueCents, "COP") }}
      </span>
    </div>
  </div>
</template>
