<script setup lang="ts">
import type { Project } from "@freelance/contracts";

defineProps<{
  project: Project;
}>();
</script>

<template>
  <UCard>
    <template #header>
      <h3 class="font-semibold">Resumen</h3>
    </template>

    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <dt class="text-sm text-muted">Cliente</dt>
        <dd class="font-medium">{{ project.client_name }}</dd>
      </div>
      <div v-if="project.client_email">
        <dt class="text-sm text-muted">Email</dt>
        <dd>{{ project.client_email }}</dd>
      </div>
      <div v-if="project.client_tax_id">
        <dt class="text-sm text-muted">NIT / CC</dt>
        <dd>{{ project.client_tax_id }}</dd>
      </div>
      <div>
        <dt class="text-sm text-muted">Moneda</dt>
        <dd>{{ project.currency }}</dd>
      </div>
      <div v-if="project.started_at">
        <dt class="text-sm text-muted">Fecha de inicio</dt>
        <dd>{{ new Date(project.started_at + 'T00:00:00').toLocaleDateString('es-CO') }}</dd>
      </div>
      <div v-if="project.completed_at">
        <dt class="text-sm text-muted">Completado</dt>
        <dd>{{ new Date(project.completed_at + 'T00:00:00').toLocaleDateString('es-CO') }}</dd>
      </div>
      <div v-if="project.quote_number">
        <dt class="text-sm text-muted">Cotización origen</dt>
        <dd>{{ project.quote_number }}</dd>
      </div>
    </dl>
  </UCard>
</template>
