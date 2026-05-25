<script setup lang="ts">
  import type { QuoteLineForm } from "~/composables/quotes/useQuoteLines";
  import { formatMoney } from "~/utils/formatMoney";

  const props = defineProps<{
    lines: QuoteLineForm[];
  }>();

  const emit = defineEmits<{
    "update:lines": [value: QuoteLineForm[]];
  }>();

  const openIndex = ref<number>(props.lines.length > 0 ? props.lines.length - 1 : 0);
  const draftValues = ref<Record<number, { quantity?: number; unit_amount?: number | null }>>({});

  watch(
    () => props.lines.length,
    (newLength, oldLength) => {
      if (newLength > oldLength) {
        openIndex.value = newLength - 1;
      } else if (openIndex.value >= newLength && newLength > 0) {
        openIndex.value = newLength - 1;
      } else if (newLength === 0) {
        openIndex.value = -1;
      }
    },
  );

  function addLine() {
    const next = [...props.lines];
    next.push({
      description: "",
      quantity: 1,
      unit_amount: null,
      sort_order: props.lines.length,
    });
    emit("update:lines", next);
  }

  function removeLine(index: number) {
    const next = [...props.lines];
    next.splice(index, 1);
    reindex(next);
    emit("update:lines", next);
  }

  function reindex(lines: QuoteLineForm[]) {
    lines.forEach((line, idx) => {
      line.sort_order = idx;
    });
  }

  function updateLineDescription(index: number, value: string) {
    const next = [...props.lines];
    next[index] = { ...next[index], description: value } as QuoteLineForm;
    emit("update:lines", next);
  }

  function updateLineQuantity(index: number, value: number) {
    const next = [...props.lines];
    next[index] = { ...next[index], quantity: value } as QuoteLineForm;
    if (draftValues.value[index]) {
      delete draftValues.value[index].quantity;
    }
    emit("update:lines", next);
  }

  function updateLineUnitAmount(index: number, value: number | null) {
    const next = [...props.lines];
    next[index] = { ...next[index], unit_amount: value ?? 0 } as QuoteLineForm;
    if (draftValues.value[index]) {
      delete draftValues.value[index].unit_amount;
    }
    emit("update:lines", next);
  }

  function updateDraftQuantity(index: number, value: number) {
    draftValues.value[index] = {
      ...draftValues.value[index],
      quantity: value,
    };
  }

  function updateDraftUnitAmount(index: number, value: number | null) {
    draftValues.value[index] = {
      ...draftValues.value[index],
      unit_amount: value,
    };
  }

  function handleOpenChange(index: number, isOpen: boolean) {
    if (isOpen) {
      openIndex.value = index;
    } else if (openIndex.value === index) {
      openIndex.value = -1;
    }
  }

  const subtotalCents = computed(() => {
    return props.lines.reduce((sum, line) => {
      const draft = draftValues.value[line.sort_order] ?? {};
      const quantity = draft.quantity ?? line.quantity;
      const unitAmount = draft.unit_amount ?? line.unit_amount ?? 0;
      return sum + Math.round(quantity * unitAmount * 100);
    }, 0);
  });
</script>

<template>
  <div class="w-full space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <p class="mt-1 text-sm text-muted">
          Describe cada servicio, entregable o bloque de trabajo incluido en la propuesta.
        </p>
      </div>
      <UButton label="Agregar concepto" icon="i-lucide-plus" size="xs" variant="outline" @click="addLine" />
    </div>

    <div class="space-y-2">
      <UCollapsible v-for="(line, index) in lines" :key="`concept-${index}`" :open="openIndex === index"
        :unmount-on-hide="false" class="rounded-xl border border-default bg-elevated/20 shadow-sm"
        @update:open="handleOpenChange(index, $event)">
        <template #default="{ open }">
          <button type="button"
            class="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left transition-colors hover:bg-elevated/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
            :aria-expanded="open" :aria-controls="`concept-content-${index}`">
            <QuotesUiQuoteConceptSummaryRow :line="line" :index="index" />
            <UIcon :name="open ? 'i-lucide-chevron-up' : 'i-lucide-chevron-down'" class="shrink-0 text-muted" />
          </button>
        </template>

        <template #content>
          <div :id="`concept-content-${index}`" class="px-4 pb-4">
            <QuotesUiQuoteConceptCard :line="line" :index="index"
              @update:description="updateLineDescription(index, $event)"
              @preview:quantity="updateDraftQuantity(index, $event)"
              @preview:unit-amount="updateDraftUnitAmount(index, $event)"
              @update:quantity="updateLineQuantity(index, $event)"
              @update:unit-amount="updateLineUnitAmount(index, $event)" @remove="removeLine(index)" />
          </div>
        </template>
      </UCollapsible>
    </div>

    <div class="flex items-center justify-between border-t border-default pt-2 text-sm text-muted">
      <span>Valor estimado de la propuesta</span>
      <span>{{ formatMoney(subtotalCents, "COP") }}</span>
    </div>
  </div>
</template>
