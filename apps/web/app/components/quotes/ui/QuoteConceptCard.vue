<script setup lang="ts">
  import type { QuoteLineForm } from "~/composables/quotes/useQuoteLines";
  import { formatMoney } from "~/utils/formatMoney";
  import { parseLocalizedNumber } from "~/utils/parseLocalizedNumber";

  const props = defineProps<{
    line: QuoteLineForm;
    index: number;
  }>();

  const emit = defineEmits<{
    "update:description": [value: string];
    "update:quantity": [value: number];
    "update:unitAmount": [value: number | null];
    "preview:quantity": [value: number];
    "preview:unitAmount": [value: number | null];
    remove: [];
  }>();

  const quantityPreview = ref<number | null>(null);
  const unitAmountPreview = ref<number | null>(null);

  function parseQuantityInput(value: string): number {
    return parseLocalizedNumber(value) ?? 0;
  }

  function parseUnitAmountInput(value: string): number | null {
    return parseLocalizedNumber(value);
  }

  watch(() => props.line.quantity, () => {
    quantityPreview.value = null;
  });

  watch(() => props.line.unit_amount, () => {
    unitAmountPreview.value = null;
  });

  const displayQuantity = computed(() => quantityPreview.value ?? props.line.quantity);
  const displayUnitAmount = computed(() => unitAmountPreview.value ?? props.line.unit_amount ?? 0);

  const conceptValueCents = computed(() => {
    return Math.round(displayQuantity.value * displayUnitAmount.value * 100);
  });

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
        <UInputNumber
          :id="`quote-line-quantity-${index}`"
          :name="`lines[${index}][quantity]`"
          :model-value="line.quantity"
          :step="0.01"
          :min="0.01"
          :increment="false"
          :decrement="false"
          class="w-full"
          placeholder="Cantidad"
          @input="quantityPreview = parseQuantityInput(($event.target as HTMLInputElement).value); emit('preview:quantity', quantityPreview)"
          @update:model-value="emit('update:quantity', ($event as number | undefined) ?? 0)"
        />
      </UFormField>

      <UFormField :label="`Valor unitario del concepto ${index + 1}`" :name="`lines[${index}][unit_amount_cents]`">
        <div class="relative">
          <span class="pointer-events-none absolute inset-y-0 left-0 z-10 flex items-center pl-3 text-sm text-muted">
            $
          </span>
          <UInputNumber
            :id="`quote-line-unit-amount-${index}`"
            :name="`lines[${index}][unit_amount_cents]`"
            :model-value="line.unit_amount ?? undefined"
            :step="0.01"
            :min="0"
            :increment="false"
            :decrement="false"
            class="w-full"
            placeholder="0,00"
            :ui="{ base: 'pl-7' }"
            @input="unitAmountPreview = parseUnitAmountInput(($event.target as HTMLInputElement).value); emit('preview:unitAmount', unitAmountPreview)"
            @update:model-value="emit('update:unitAmount', ($event as number | undefined) ?? null)"
          />
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
