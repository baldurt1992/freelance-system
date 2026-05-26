<script setup lang="ts">
import type { QuoteStatus } from "@freelance/contracts";

defineProps<{
  status: QuoteStatus;
  loadingAction: string | null;
  converting: boolean;
}>();

defineEmits<{
  send: [];
  accept: [];
  reject: [];
  convert: [];
}>();
</script>

<template>
  <div class="space-y-4">
    <div v-if="status === 'draft' || status === 'sent'" class="flex gap-2">
      <UButton
        v-if="status === 'draft'"
        label="Enviar"
        icon="i-lucide-send"
        :loading="loadingAction === 'send'"
        @click="$emit('send')"
      />
      <UButton
        v-if="status === 'sent'"
        label="Aceptar"
        icon="i-lucide-check"
        color="success"
        :loading="loadingAction === 'accept'"
        @click="$emit('accept')"
      />
      <UButton
        v-if="status === 'sent'"
        label="Rechazar"
        icon="i-lucide-x"
        color="error"
        variant="outline"
        :loading="loadingAction === 'reject'"
        @click="$emit('reject')"
      />
    </div>

    <div v-if="status === 'accepted'" class="flex gap-2">
      <UButton
        label="Convertir a proyecto"
        icon="i-lucide-briefcase-business"
        color="primary"
        :loading="converting"
        @click="$emit('convert')"
      />
    </div>
  </div>
</template>
