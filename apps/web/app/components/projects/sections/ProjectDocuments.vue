<script setup lang="ts">
import type { BillingDocument, Project } from "@freelance/contracts";
import { getBillingStatusColor, getBillingStatusLabel } from "~/utils/billingStatus";

const props = defineProps<{
  project: Project;
  billingDocuments: BillingDocument[];
}>();

const emit = defineEmits<{
  "download-billing": [billingDocumentId: number];
}>();

const router = useRouter();

function goToQuote() {
  if (props.project.quote_id) {
    router.push(`/quotes/${props.project.quote_id}`);
  }
}
</script>

<template>
  <UCard>
    <template #header>
      <h3 class="font-semibold">Documentos</h3>
    </template>

    <div class="space-y-3">
      <div
        v-if="project.quote_id && project.quote_number"
        class="flex items-center justify-between gap-3 rounded-lg border border-default p-3"
      >
        <div class="min-w-0">
          <p class="text-sm font-medium">Cotización</p>
          <p class="text-xs text-muted truncate">{{ project.quote_number }}</p>
        </div>
        <UButton
          label="Ver cotización"
          icon="i-lucide-file-text"
          variant="outline"
          size="sm"
          @click="goToQuote"
        />
      </div>

      <div
        v-for="doc in billingDocuments"
        :key="doc.id"
        class="flex items-center justify-between gap-3 rounded-lg border border-default p-3"
      >
        <div class="min-w-0 space-y-1">
          <div class="flex flex-wrap items-center gap-2">
            <p class="text-sm font-medium">Cuenta de cobro</p>
            <UBadge
              :label="getBillingStatusLabel(doc.status)"
              :color="getBillingStatusColor(doc.status)"
              variant="subtle"
              size="sm"
            />
          </div>
          <p class="text-xs text-muted truncate">{{ doc.number }}</p>
          <p v-if="doc.sent_at" class="text-xs text-muted">
            Enviada {{ new Date(doc.sent_at).toLocaleDateString('es-CO') }}
          </p>
        </div>
        <UButton
          label="PDF"
          icon="i-lucide-file-down"
          variant="outline"
          size="sm"
          @click="emit('download-billing', doc.id)"
        />
      </div>

      <p v-if="!project.quote_id && billingDocuments.length === 0" class="text-sm text-muted text-center py-2">
        Sin documentos asociados.
      </p>
    </div>
  </UCard>
</template>
