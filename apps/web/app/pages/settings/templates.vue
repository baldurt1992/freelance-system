<script setup lang="ts">
import type { DocumentTemplate, DocumentTemplateType } from "@freelance/contracts";

const { toastApiError } = useApiError();
const { list, update, preview } = useDocumentTemplatesApi();
const toast = useToast();

const templateType = ref<DocumentTemplateType>("quote");
const loading = ref(false);
const saving = ref(false);
const previewing = ref(false);
const selectedTemplate = ref<DocumentTemplate | null>(null);
const htmlBody = ref("");

const typeOptions = [
  { label: "Cotización", value: "quote" as const },
  { label: "Cuenta de cobro", value: "billing" as const },
];

async function loadTemplates() {
  loading.value = true;
  try {
    const response = await list(templateType.value);
    const defaultTemplate = response.data.find((item) => item.is_default) ?? response.data[0] ?? null;
    selectedTemplate.value = defaultTemplate;
    htmlBody.value = defaultTemplate?.html_body ?? "";
  } catch (error) {
    toastApiError(error, { fallback: "No se pudieron cargar las plantillas." });
  } finally {
    loading.value = false;
  }
}

watch(templateType, () => {
  loadTemplates();
}, { immediate: true });

async function onSave() {
  if (!selectedTemplate.value) return;

  saving.value = true;
  try {
    const updated = await update(selectedTemplate.value.id, { html_body: htmlBody.value });
    selectedTemplate.value = updated;
    toast.add({ title: "Plantilla guardada" });
  } catch (error) {
    toastApiError(error, { fallback: "No se pudo guardar la plantilla." });
  } finally {
    saving.value = false;
  }
}

async function onPreview() {
  previewing.value = true;
  try {
    const blob = await preview({
      type: templateType.value,
      html_body: htmlBody.value,
      template_id: selectedTemplate.value?.id,
    });
    const url = URL.createObjectURL(blob);
    window.open(url, "_blank", "noopener,noreferrer");
    setTimeout(() => URL.revokeObjectURL(url), 60_000);
  } catch (error) {
    toastApiError(error, { fallback: "No se pudo generar la vista previa." });
  } finally {
    previewing.value = false;
  }
}
</script>

<template>
  <div class="space-y-6">
    <UPageCard
      title="Plantillas PDF"
      description="Edita el HTML de cotizaciones y cuentas de cobro. Usa variables como {{client_name}} o {{lines_table}}."
      variant="naked"
      orientation="horizontal"
      class="mb-2"
    >
      <div class="flex flex-wrap gap-2 lg:ms-auto">
        <UButton
          label="Vista previa"
          icon="i-lucide-eye"
          variant="outline"
          :loading="previewing"
          :disabled="!htmlBody || loading"
          @click="onPreview"
        />
        <UButton
          label="Guardar"
          icon="i-lucide-save"
          :loading="saving"
          :disabled="!selectedTemplate || loading"
          @click="onSave"
        />
      </div>
    </UPageCard>

    <UPageCard variant="subtle" class="space-y-4">
      <UFormField
        name="template_type"
        label="Tipo de documento"
        class="max-w-xs"
      >
        <USelect
          id="template-type"
          v-model="templateType"
          :items="typeOptions"
          value-key="value"
          label-key="label"
          aria-label="Tipo de plantilla"
        />
      </UFormField>

      <UFormField
        v-if="selectedTemplate"
        name="html_body"
        label="HTML"
        :description="`Plantilla: ${selectedTemplate.name}`"
        :ui="{ container: 'w-full' }"
      >
        <UTextarea
          id="template-html"
          v-model="htmlBody"
          :rows="22"
          autoresize
          class="w-full font-mono text-sm"
          aria-label="Contenido HTML de la plantilla"
        />
      </UFormField>

      <div v-else-if="loading" class="text-sm text-muted py-4">Cargando plantilla...</div>
    </UPageCard>
  </div>
</template>
