<script setup lang="ts">
  import PageContentWide from "~/components/ui/PageContentWide.vue";
  import PageSectionCard from "~/components/ui/PageSectionCard.vue";
  import { useQuotesApi } from "~/composables/quotes/useQuotesApi";
  import { useClientsApi } from "~/composables/clients/useClientsApi";
  import { useQuoteForm } from "~/composables/quotes/useQuoteForm";
  import { serializeQuotePayload } from "~/composables/quotes/quoteFormHelpers";

  definePageMeta({ layout: "default" });

  const toast = useToast();
  const router = useRouter();
  const { toastApiError } = useApiError();
  const { create } = useQuotesApi();
  const { list: listClients } = useClientsApi();

  const { data: clientsData } = useAsyncData("clients-for-select", () => listClients(1, ""));
  const clients = computed(() => clientsData.value?.data ?? []);
  const selectedClient = computed(() => {
    return clients.value.find((client) => client.id === form.clientId.value);
  });

  const form = useQuoteForm();
  const saving = ref(false);

  async function onSubmit() {
    if (!form.clientId.value) {
      toast.add({ title: "Selecciona un cliente", color: "warning" });
      return;
    }

    if (form.lines.value.some((l) => !l.description || !l.unit_amount || l.unit_amount <= 0)) {
      toast.add({ title: "Revisa las líneas", color: "warning" });
      return;
    }

    saving.value = true;
    try {
      const payload = serializeQuotePayload(
        form.title.value,
        form.notes.value,
        form.validUntil.value,
        form.lines.value,
      );

      const quote = await create({ client_id: form.clientId.value, ...payload });
      toast.add({ title: "Cotización creada", color: "success" });
      router.push(`/quotes/${quote.id}`);
    } catch (error) {
      toastApiError(error, { fallback: "No se pudo crear la cotización." });
    } finally {
      saving.value = false;
    }
  }
</script>

<template>
  <UDashboardPanel id="quotes-new">
    <template #header>
      <UDashboardNavbar title="Nueva cotización">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton label="Volver" icon="i-lucide-arrow-left" variant="ghost" @click="router.push('/quotes')" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <PageContentWide class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem] xl:items-start">
        <form class="space-y-6" @submit.prevent="onSubmit">
          <PageSectionCard>
            <div class="space-y-4">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-muted">Datos generales</p>
                <h2 class="mt-1 text-lg font-semibold text-highlighted">Información de la cotización</h2>
              </div>

              <QuotesUiQuoteFormFields :clients="clients" :client-id="form.clientId.value" :title="form.title.value"
                :notes="form.notes.value" :valid-until="form.validUntil.value"
                @update:client-id="form.clientId.value = $event" @update:title="form.title.value = $event"
                @update:notes="form.notes.value = $event" @update:valid-until="form.validUntil.value = $event" />
            </div>
          </PageSectionCard>

          <PageSectionCard>
            <div class="space-y-4">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-muted">Propuesta</p>
                <h2 class="mt-1 text-lg font-semibold text-highlighted">Alcance de la cotización</h2>
              </div>

              <QuotesUiQuoteLinesEditor :lines="form.lines.value" @update:lines="form.lines.value = $event" />
            </div>
          </PageSectionCard>

          <div class="flex items-center gap-3">
            <div class="flex-1" />
            <UButton variant="outline" @click="router.push('/quotes')">
              Cancelar
            </UButton>
            <UButton type="submit" :loading="saving">
              Guardar cotización
            </UButton>
          </div>
        </form>

        <QuotesSectionsQuoteDraftSummaryCard :client-name="selectedClient?.name" :title="form.title.value"
          :valid-until="form.validUntil.value" :lines="form.lines.value" />
      </PageContentWide>
    </template>
  </UDashboardPanel>
</template>
