<script setup lang="ts">
const tenant = useTenantStore();
const { toastApiError } = useApiError();
const { updateSettings } = useSettingsApi();
const toast = useToast();

const loading = ref(false);
const taxEnabled = ref(tenant.taxEnabled);

watch(
  () => tenant.taxEnabled,
  (value) => {
    taxEnabled.value = value;
  },
);

async function onTaxToggle(value: boolean) {
  loading.value = true;
  try {
    const updated = await updateSettings({ tax_enabled: value });
    tenant.setTenant({
      id: updated.id,
      name: updated.name,
      tax_enabled: updated.tax_enabled,
      currency: updated.currency,
      tax_rate: updated.tax_rate,
    });
    toast.add({
      title: value ? "IVA activado" : "IVA desactivado",
      description: "Aplica a cotizaciones nuevas y borradores. Los documentos históricos no cambian.",
    });
  } catch (error) {
    taxEnabled.value = tenant.taxEnabled;
    toastApiError(error, { fallback: "No se pudo actualizar la configuración de IVA." });
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="space-y-6">
    <UPageCard
      title="Facturación e IVA"
      description="Configura si las cotizaciones nuevas incluyen desglose de impuestos."
      variant="naked"
      orientation="horizontal"
    />

    <UPageCard variant="subtle">
      <UFormField
        name="tax_enabled"
        label="Activar IVA"
        :description="`Tasa configurada: ${tenant.taxRate}% (${tenant.currency}). Solo afecta cotizaciones nuevas y borradores.`"
        class="flex max-sm:flex-col justify-between items-start gap-4"
      >
        <USwitch
          id="tax-enabled"
          v-model="taxEnabled"
          :loading="loading"
          aria-label="Activar IVA en cotizaciones"
          @update:model-value="onTaxToggle"
        />
      </UFormField>

      <UAlert
        class="mt-4"
        color="info"
        variant="subtle"
        icon="i-lucide-info"
        title="Documentos históricos"
        description="Cotizaciones enviadas o aceptadas, proyectos con pagos y cuentas de cobro emitidas no se modifican al cambiar el IVA."
      />
    </UPageCard>
  </div>
</template>
