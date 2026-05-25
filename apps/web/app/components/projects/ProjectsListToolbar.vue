<script setup lang="ts">
import { upperFirst } from "scule";
import type { ProjectsTableApi } from "~/types/projects-table";
import ProjectsDeleteModal from "~/components/projects/ProjectsDeleteModal.vue";

const searchQuery = defineModel<string>("searchQuery", { required: true });

defineProps<{
  selectedCount: number;
  tableApi: ProjectsTableApi | null;
}>();

const emit = defineEmits<{
  confirmBatchDelete: [];
}>();
</script>

<template>
  <div class="flex flex-wrap items-center justify-between gap-1.5">
    <UInput
      id="projects-search"
      name="projects-search"
      v-model="searchQuery"
      type="search"
      class="max-w-sm"
      icon="i-lucide-search"
      placeholder="Buscar proyectos..."
      autocomplete="off"
      aria-label="Buscar proyectos"
    />

    <div class="flex flex-wrap items-center gap-1.5">
      <ProjectsDeleteModal
        :count="selectedCount"
        @confirm="emit('confirmBatchDelete')"
      >
        <UButton
          v-if="selectedCount"
          label="Eliminar"
          color="error"
          variant="subtle"
          icon="i-lucide-trash"
        >
          <template #trailing>
            <UKbd>{{ selectedCount }}</UKbd>
          </template>
        </UButton>
      </ProjectsDeleteModal>

      <UDropdownMenu
        v-if="tableApi"
        :items="tableApi
          .getAllColumns()
          .filter((column) => column.getCanHide())
          .map((column) => ({
            label: upperFirst(column.id),
            type: 'checkbox' as const,
            checked: column.getIsVisible(),
            onUpdateChecked(checked: boolean) {
              tableApi?.getColumn(column.id)?.toggleVisibility(!!checked);
            },
            onSelect(e?: Event) {
              e?.preventDefault();
            },
          }))
        "
        :content="{ align: 'end' }"
      >
        <UButton label="Columnas" color="neutral" variant="outline" trailing-icon="i-lucide-settings-2" />
      </UDropdownMenu>
    </div>
  </div>
</template>
