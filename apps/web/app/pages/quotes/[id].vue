<script setup lang="ts">
import { useQuotesApi } from "~/composables/quotes/useQuotesApi";
import { getQuoteStatusLabel, getQuoteStatusColor } from "~/utils/quoteStatus";
import type { BadgeColor } from "~/utils/quoteStatus";

definePageMeta({ layout: "default" });

const route = useRoute();
const router = useRouter();
const { find } = useQuotesApi();

const quoteId = computed(() => Number(route.params.id));

const { data: quote, status, refresh } = useAsyncData(
  () => `quote-${quoteId.value}`,
  () => find(quoteId.value),
  { server: false, watch: [quoteId] },
);

const quoteNumber = computed(() => quote.value?.number);

const {
  loadingAction,
  converting,
  doAction,
  onDownloadPdf,
  onConvertToProject,
} = useQuoteDetailActions(quoteId, refresh, quoteNumber);

const statusLabel = computed(() => getQuoteStatusLabel(quote.value?.status ?? ""));
const statusColor = computed<BadgeColor>(() => getQuoteStatusColor(quote.value?.status ?? ""));
</script>

<template>
  <UDashboardPanel id="quote-detail">
    <template #header>
      <UDashboardNavbar :title="quote ? `Cotización ${quote.number}` : 'Detalle de cotización'">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            label="Volver"
            icon="i-lucide-arrow-left"
            variant="ghost"
            @click="router.push('/quotes')"
          />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <UCard v-if="status === 'pending'">
        <div class="py-8 text-center text-muted">Cargando...</div>
      </UCard>

      <UCard v-else-if="!quote">
        <div class="py-8 text-center text-muted">Cotización no encontrada.</div>
      </UCard>

      <div v-else class="max-w-4xl space-y-6">
        <div class="flex items-center gap-3">
          <UBadge :label="statusLabel" :color="statusColor" variant="subtle" />
          <UButton
            icon="i-lucide-file-down"
            variant="outline"
            size="sm"
            @click="onDownloadPdf"
          >
            PDF
          </UButton>
        </div>

        <QuotesSectionsQuoteDetailCard :quote="quote" />
        <QuotesSectionsQuoteLinesTable :quote="quote" />

        <QuotesSectionsQuoteActions
          :status="quote.status"
          :loading-action="loadingAction"
          :converting="converting"
          @send="doAction('send')"
          @accept="doAction('accept')"
          @reject="doAction('reject')"
          @convert="onConvertToProject"
        />
      </div>
    </template>
  </UDashboardPanel>
</template>
