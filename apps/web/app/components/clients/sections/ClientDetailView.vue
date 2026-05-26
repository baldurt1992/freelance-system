<script setup lang="ts">
import type { Client } from "@freelance/contracts";

defineProps<{
  client: Client;
}>();

defineEmits<{
  edit: [];
}>();
</script>

<template>
  <div>
    <div class="mb-6 flex items-center gap-4">
      <UAvatar
        :src="client.avatar || undefined"
        :alt="client.name"
        size="3xl"
      />
      <div>
        <h2 class="text-2xl font-semibold">
          {{ client.name }}
        </h2>
        <p v-if="client.email" class="text-sm text-muted">
          {{ client.email }}
        </p>
      </div>
      <div class="flex-1" />
      <UButton
        icon="i-lucide-pencil"
        @click="$emit('edit')"
      >
        Editar
      </UButton>
    </div>

    <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
      <div>
        <dt class="text-sm text-muted">Teléfono</dt>
        <dd>{{ client.phone || "—" }}</dd>
      </div>
      <div>
        <dt class="text-sm text-muted">NIT / CC</dt>
        <dd>{{ client.tax_id || "—" }}</dd>
      </div>
      <div>
        <dt class="text-sm text-muted">Dirección</dt>
        <dd>{{ client.address || "—" }}</dd>
      </div>
    </dl>

    <div v-if="client.notes" class="mt-4">
      <dt class="text-sm text-muted">Notas</dt>
      <dd class="whitespace-pre-wrap">{{ client.notes }}</dd>
    </div>
  </div>
</template>
