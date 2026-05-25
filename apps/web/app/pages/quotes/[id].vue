<script setup lang="ts">
import { useQuotesApi } from "~/composables/quotes/useQuotesApi";
import { useProjectsApi } from "~/composables/projects/useProjectsApi";
import { getQuoteStatusLabel, getQuoteStatusColor } from "~/utils/quoteStatus";
import type { BadgeColor } from "~/utils/quoteStatus";

definePageMeta({ layout: "default" });

const route = useRoute();
const router = useRouter();
const toast = useToast();
const { toastApiError } = useApiError();
const { find, send, accept, reject, downloadPdf } = useQuotesApi();
const { convertQuoteToProject } = useProjectsApi();

const quoteId = computed(() => Number(route.params.id));

const { data: quote, status, refresh } = useAsyncData(
  () => `quote-${quoteId.value}`,
  () => find(quoteId.value),
  { server: false, watch: [quoteId] },
);

const loadingAction = ref<string | null>(null);
const converting = ref(false);

async function doAction(action: "send" | "accept" | "reject") {
  loadingAction.value = action;
  try {
    const handlers = { send, accept, reject };
    await handlers[action](quoteId.value);
    toast.add({
      title: action === "send" ? "Enviada" : action === "accept" ? "Aceptada" : "Rechazada",
    });
    await refresh();
  } catch (error) {
    toastApiError(error, { fallback: `No se pudo ${action} la cotización.` });
  } finally {
    loadingAction.value = null;
  }
}

async function onDownloadPdf() {
  try {
    const blob = await downloadPdf(quoteId.value);
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `${quote.value?.number ?? "cotizacion"}.pdf`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  } catch (error) {
    toastApiError(error, { fallback: "No se pudo descargar el PDF." });
  }
}

async function onConvertToProject() {
  converting.value = true;
  try {
    const project = await convertQuoteToProject(quoteId.value);
    toast.add({ title: "Proyecto creado desde cotización" });
    await router.push(`/projects/${project.id}`);
  } catch (error) {
    toastApiError(error, { fallback: "No se pudo convertir la cotización a proyecto." });
  } finally {
    converting.value = false;
  }
}

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
        <div class="text-center py-8 text-muted">Cargando...</div>
      </UCard>

      <UCard v-else-if="!quote">
        <div class="text-center py-8 text-muted">Cotización no encontrada.</div>
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

        <div v-if="quote.status === 'draft' || quote.status === 'sent'" class="flex gap-2">
          <UButton
            v-if="quote.status === 'draft'"
            label="Enviar"
            icon="i-lucide-send"
            :loading="loadingAction === 'send'"
            @click="doAction('send')"
          />
          <UButton
            v-if="quote.status === 'sent'"
            label="Aceptar"
            icon="i-lucide-check"
            color="success"
            :loading="loadingAction === 'accept'"
            @click="doAction('accept')"
          />
          <UButton
            v-if="quote.status === 'sent'"
            label="Rechazar"
            icon="i-lucide-x"
            color="error"
            variant="outline"
            :loading="loadingAction === 'reject'"
            @click="doAction('reject')"
          />
        </div>

        <div v-if="quote.status === 'accepted'" class="flex gap-2">
          <UButton
            label="Convertir a proyecto"
            icon="i-lucide-briefcase-business"
            color="primary"
            :loading="converting"
            @click="onConvertToProject"
          />
        </div>
      </div>
    </template>
  </UDashboardPanel>
</template>
