<script setup lang="ts">
  import type { QuoteLineForm } from "~/composables/quotes/useQuoteLines";
  import { formatMoney } from "~/utils/formatMoney";

  const props = defineProps<{
    line: QuoteLineForm;
    index: number;
  }>();

  const emit = defineEmits<{
    "update:description": [value: string];
    "update:quantity": [value: number];
    "update:unitAmount": [value: number | null];
    remove: [];
  }>();

  const conceptValueCents = computed(() => {
    return Math.round(props.line.quantity * (props.line.unit_amount ?? 0) * 100);
  });

  function parseQuantityInput(value: unknown): number {
    if (value === "" || value === null || value === undefined) return 0;
    const num = Number(value);
    return Number.isNaN(num) ? 0 : num;
  }

  function parseUnitAmountInput(value: unknown): number | null {
    if (value === "" || value === null || value === undefined) return null;
    const num = Number(value);
    return Number.isNaN(num) ? null : num;
  }
</script>

<template>
  <div class="space-y-4 pt-2">
    <UFormField :label="`Descripción del concepto ${index + 1}`" :name="`lines[${index}][description]`">
      <UInput :id="`quote-line-description-${index}`" :name="`lines[${index}][description]`"
        :model-value="line.description" class="w-full" placeholder="Ej. Diseño de identidad visual" autocomplete="off"
        @update:model-value="emit('update:description', $event as string)" />
    </UFormField>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
      <UFormField :label="`Cantidad del concepto (horas, piezas, etc.) ${index + 1}`"
        :name="`lines[${index}][quantity]`">
        <UInput :id="`quote-line-quantity-${index}`" :name="`lines[${index}][quantity]`"
          :model-value="line.quantity" type="number" step="0.01" min="0.01"
          class="w-full" placeholder="Cantidad" autocomplete="off"
          @wheel.prevent @update:model-value="emit('update:quantity', parseQuantityInput($event))" />
      </UFormField>

      <UFormField :label="`Valor unitario del concepto ${index + 1}`" :name="`lines[${index}][unit_amount_cents]`">
        <div class="relative">
          <span class="pointer-events-none absolute inset-y-0 left-0 z-10 flex items-center pl-3 text-sm text-muted">
            $
          </span>
          <UInput :id="`quote-line-unit-amount-${index}`" :name="`lines[${index}][unit_amount_cents]`"
            :model-value="line.unit_amount" type="number" step="0.01" min="0"
            class="w-full" placeholder="0,00" :ui="{ base: 'pl-7' }" autocomplete="off" @wheel.prevent
            @update:model-value="emit('update:unitAmount', parseUnitAmountInput($event))" />
        </div>
      </UFormField>
    </div>

    <div class="flex items-center justify-between rounded-lg border border-default/70 bg-background/60 px-4 py-3">
      <span class="text-sm text-muted">Valor estimado de este concepto</span>
      <span class="text-base font-semibold text-highlighted">
        {{ formatMoney(conceptValueCents, "COP") }}
      </span>
    </div>

    <div class="flex justify-end">
      <UButton icon="i-lucide-trash" variant="ghost" size="xs" color="error"
        :aria-label="`Eliminar concepto ${index + 1}`" @click="emit('remove')">
        Eliminar concepto
      </UButton>
    </div>
  </div>
</template>
