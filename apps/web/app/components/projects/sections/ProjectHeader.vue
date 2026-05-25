<script setup lang="ts">
import type { Project } from "@freelance/contracts";

defineProps<{
  project: Project;
}>();

const router = useRouter();

const typeLabel: Record<string, string> = {
  freelance: "Freelance",
  fixed: "Precio fijo",
  retainer: "Retainer",
};

const statusLabel: Record<string, string> = {
  active: "Activo",
  on_hold: "En pausa",
  completed: "Completado",
  cancelled: "Cancelado",
};
</script>

<template>
  <div>
    <div class="flex items-center gap-3 mb-4">
      <UButton
        icon="i-lucide-arrow-left"
        variant="ghost"
        @click="router.push('/projects')"
        aria-label="Volver a proyectos"
      />
      <div>
        <h1 class="text-2xl font-semibold">{{ project.name }}</h1>
        <p class="text-sm text-muted">
          {{ project.client_name }}
          <template v-if="project.quote_number">
            &middot; <span class="text-blue-500">Cotización {{ project.quote_number }}</span>
          </template>
          &middot; {{ typeLabel[project.type] || project.type }}
        </p>
      </div>
    </div>

    <div class="flex flex-wrap items-center gap-2">
      <UBadge
        :label="project.is_fully_paid ? 'Pagado totalmente' : statusLabel[project.status] || project.status"
        :color="project.is_fully_paid ? 'success' : project.status === 'active' ? 'info' : 'neutral'"
        variant="subtle"
      />
      <UBadge
        v-if="!project.is_fully_paid && project.balance_due_cents > 0"
        :label="`Por cobrar`"
        color="warning"
        variant="subtle"
      />
    </div>
  </div>
</template>
