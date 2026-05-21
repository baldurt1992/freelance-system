<script setup lang="ts">
import type { Quote } from "@freelance/contracts";
import { formatMoney } from "~/utils/formatMoney";

const props = defineProps<{
  quote: Quote;
}>();
</script>

<template>
  <div class="rounded-lg border border-default p-4">
    <h3 class="mb-2 text-sm font-semibold uppercase text-muted">Conceptos</h3>
    <table class="w-full text-left text-sm">
      <thead>
        <tr class="border-b border-default">
          <th class="py-2">#</th>
          <th class="py-2">Concepto</th>
          <th class="py-2 text-right">Cantidad</th>
          <th class="py-2 text-right">Valor unit.</th>
          <th class="py-2 text-right">Total</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(line, idx) in quote.lines" :key="line.id" class="border-b border-default/50">
          <td class="py-2">{{ idx + 1 }}</td>
          <td class="py-2">{{ line.description }}</td>
          <td class="py-2 text-right">{{ line.quantity }}</td>
          <td class="py-2 text-right">{{ formatMoney(line.unit_amount_cents, quote.currency) }}</td>
          <td class="py-2 text-right font-medium">{{ formatMoney(line.line_total_cents, quote.currency) }}</td>
        </tr>
      </tbody>
    </table>

    <div class="mt-4 flex flex-col items-end gap-1 text-sm">
      <div class="flex w-64 justify-between">
        <span class="text-muted">Subtotal</span>
        <span>{{ formatMoney(quote.subtotal_cents, quote.currency) }}</span>
      </div>
      <div class="flex w-64 justify-between">
        <span class="text-muted">Impuestos</span>
        <span>{{ formatMoney(quote.tax_cents, quote.currency) }}</span>
      </div>
      <div class="flex w-64 justify-between border-t border-default pt-1 text-base font-bold">
        <span>Total</span>
        <span>{{ formatMoney(quote.total_cents, quote.currency) }}</span>
      </div>
    </div>
  </div>
</template>
