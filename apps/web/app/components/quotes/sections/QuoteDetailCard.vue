<script setup lang="ts">
import type { Quote } from "@freelance/contracts";

const props = defineProps<{
  quote: Quote;
}>();

function formatDate(iso: string | null | undefined): string {
  if (!iso) return "—";
  if (/^\d{4}-\d{2}-\d{2}$/.test(iso)) {
    const [yearString, monthString, dayString] = iso.split("-");
    const year = Number(yearString);
    const month = Number(monthString);
    const day = Number(dayString);
    return new Intl.DateTimeFormat("es-CO", {
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
    }).format(new Date(year, month - 1, day));
  }
  return new Date(iso).toLocaleDateString("es-CO");
}
</script>

<template>
  <div class="space-y-4">
    <div class="rounded-lg border border-default p-4">
      <h3 class="mb-2 text-sm font-semibold uppercase text-muted">Cliente</h3>
      <p class="font-medium">{{ quote.client_name }}</p>
      <p v-if="quote.client_email" class="text-sm text-muted">{{ quote.client_email }}</p>
      <p v-if="quote.client_tax_id" class="text-sm text-muted">NIT / CC: {{ quote.client_tax_id }}</p>
      <p v-if="quote.client_address" class="text-sm text-muted">{{ quote.client_address }}</p>
    </div>

    <div class="rounded-lg border border-default p-4">
      <h3 class="mb-2 text-sm font-semibold uppercase text-muted">Información</h3>
      <p v-if="quote.title" class="font-medium">{{ quote.title }}</p>
      <p v-if="quote.notes" class="mt-1 text-sm">{{ quote.notes }}</p>
      <div class="mt-2 flex gap-4 text-sm text-muted">
        <span v-if="quote.valid_until">Válida hasta: {{ formatDate(quote.valid_until) }}</span>
        <span>Creada: {{ formatDate(quote.created_at) }}</span>
      </div>
      <div v-if="quote.sent_at || quote.accepted_at || quote.rejected_at" class="mt-2 text-sm text-muted">
        <span v-if="quote.sent_at">Enviada: {{ formatDate(quote.sent_at) }} </span>
        <span v-if="quote.accepted_at">Aceptada: {{ formatDate(quote.accepted_at) }} </span>
        <span v-if="quote.rejected_at">Rechazada: {{ formatDate(quote.rejected_at) }} </span>
      </div>
    </div>
  </div>
</template>
