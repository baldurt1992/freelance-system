<script setup lang="ts">
import type { Project } from "@freelance/contracts";

const props = defineProps<{
  project: Project;
}>();

const emit = defineEmits<{
  completed: [];
}>();

const { toastApiError } = useApiError();
const { complete } = useProjectsApi();
const toast = useToast();

const loading = ref(false);
const showConfirmDialog = ref(false);

const canComplete = computed(() => props.project.status === "active");

async function onComplete() {
  loading.value = true;
  try {
    await complete(props.project.id);
    toast.add({ title: "Proyecto completado", description: "Cuenta de cobro emitida." });
    showConfirmDialog.value = false;
    emit("completed");
  } catch (error) {
    toastApiError(error, { fallback: "No se pudo completar el proyecto." });
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <UCard v-if="canComplete || project.status === 'completed'">
    <template #header>
      <h3 class="font-semibold">Acciones</h3>
    </template>

    <div class="space-y-3">
      <p v-if="project.status === 'completed'" class="text-sm text-muted">
        Proyecto completado
        <template v-if="project.completed_at">
          el {{ new Date(project.completed_at + 'T00:00:00').toLocaleDateString('es-CO') }}.
        </template>
      </p>

      <UButton
        v-if="canComplete"
        block
        label="Completar proyecto"
        icon="i-lucide-check-check"
        color="primary"
        :loading="loading"
        @click="showConfirmDialog = true"
      />
    </div>
  </UCard>

  <UModal
    v-model:open="showConfirmDialog"
    title="Completar proyecto"
    :ui="{ content: 'max-w-sm', footer: 'justify-end' }"
  >
    <template #body>
      <p class="text-sm">
        Vas a marcar <strong>{{ project.name }}</strong> como completado.
        Se emitirá la cuenta de cobro y se enviará por correo al cliente si tiene email registrado.
      </p>
    </template>
    <template #footer>
      <div class="flex gap-2">
        <UButton label="Cancelar" variant="outline" @click="showConfirmDialog = false" />
        <UButton
          label="Confirmar"
          color="primary"
          :loading="loading"
          @click="onComplete"
        />
      </div>
    </template>
  </UModal>
</template>
