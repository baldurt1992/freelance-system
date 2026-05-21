<script setup lang="ts">
import type { QuoteLineForm } from "~/composables/quotes/useQuoteLines";
import { formatMoney } from "~/utils/formatMoney";

const props = defineProps<{
  clientName?: string;
  title: string | null;
  validUntil?: string;
  lines: QuoteLineForm[];
  currency?: string;
}>();

const subtotalCents = computed(() => {
  return props.lines.reduce((sum, line) => {
    return sum + Math.round(line.quantity * (line.unit_amount ?? 0) * 100);
  }, 0);
});

const validUntilLabel = computed(() => {
  if (!props.validUntil) return "Sin fecha";
  const [yearString, monthString, dayString] = props.validUntil.split("-");
  const year = Number(yearString);
  const month = Number(monthString);
  const day = Number(dayString);

  if ([year, month, day].some((value) => Number.isNaN(value))) {
    return props.validUntil;
  }

  return new Intl.DateTimeFormat("es-CO", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  }).format(new Date(year, month - 1, day));
});
</script>

<template>
  <UCard class="w-full shadow-sm xl:sticky xl:top-6">
    <div class="space-y-6">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-muted">Resumen</p>
        <h2 class="mt-1 text-xl font-semibold text-highlighted">
          {{ title || "Nueva cotización" }}
        </h2>
        <p class="mt-2 text-sm leading-6 text-muted">
          {{ clientName || "Selecciona un cliente para completar el documento." }}
        </p>
      </div>

      <div class="space-y-4 rounded-xl border border-default bg-elevated/30 p-5 shadow-sm">
        <div class="flex items-center justify-between text-sm">
          <span class="text-muted">Válida hasta</span>
          <span class="font-medium">{{ validUntilLabel }}</span>
        </div>
        <div class="flex items-center justify-between text-sm">
          <span class="text-muted">Conceptos incluidos</span>
          <span class="font-medium">{{ lines.length }}</span>
        </div>
        <div class="space-y-2 border-t border-default pt-4">
          <p class="text-sm text-muted">Valor estimado antes de impuestos</p>
          <span class="block text-2xl font-semibold text-highlighted sm:text-3xl">
            {{ formatMoney(subtotalCents, currency || "COP") }}
          </span>
        </div>
      </div>

      <div class="rounded-xl border border-default/70 bg-muted/20 p-4 shadow-sm">
        <p class="text-sm leading-6 text-muted">
          Este valor te ayuda a revisar rápidamente el alcance y el monto estimado de la propuesta antes de guardarla.
        </p>
      </div>
    </div>
  </UCard>
</template>
