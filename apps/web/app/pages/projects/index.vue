<script setup lang="ts">
import type { Row } from "@tanstack/table-core";
import type { Project } from "@freelance/contracts";
import type { ProjectsTableApi, ProjectsTableExpose } from "~/types/projects-table";
import { useProjectsApi } from "~/composables/projects/useProjectsApi";
import { useProjectsTableColumns } from "~/composables/projects/useProjectsTableColumns";

const toast = useToast();
const router = useRouter();
const { toastApiError } = useApiError();
const table = useTemplateRef<ProjectsTableExpose>("table");

const { list, remove } = useProjectsApi();

const page = ref(1);
const searchQuery = ref("");
const debouncedSearch = ref("");

watchDebounced(
  searchQuery,
  (value) => {
    debouncedSearch.value = value;
    page.value = 1;
  },
  { debounce: 300 },
);

const { data, status, refresh } = useAsyncData(
  () => `projects-list-${page.value}-${debouncedSearch.value}`,
  () => list(page.value, debouncedSearch.value),
  { watch: [page, debouncedSearch] },
);

const projects = computed(() => data.value?.data ?? []);
const meta = computed(() => data.value?.meta);
const loading = computed(() => status.value === "pending");

const columnVisibility = ref();
const rowSelection = ref<Record<string, boolean>>({});

function navigateToDetail(id: number) {
  router.push(`/projects/${id}`);
}

async function onDelete(row: Row<Project>) {
  try {
    await remove(row.original.id);
    toast.add({ title: "Proyecto eliminado" });
    await refresh();
  } catch (error) {
    toastApiError(error, { fallback: "No se pudo eliminar el proyecto." });
  }
}

const { columns } = useProjectsTableColumns({
  onNavigateDetail: navigateToDetail,
  onDelete,
});

function getTableApi(): ProjectsTableApi | null {
  return table.value?.tableApi ?? null;
}

const selectedCount = computed((): number => {
  return getTableApi()?.getFilteredSelectedRowModel().rows.length ?? 0;
});

const tableApi = computed((): ProjectsTableApi | null => getTableApi());

async function onBatchDelete() {
  const rows = getTableApi()?.getFilteredSelectedRowModel().rows ?? [];
  const ids = rows.map((r) => r.original.id);
  if (!ids.length) return;

  try {
    await Promise.all(ids.map((id) => remove(id)));
    toast.add({ title: `${ids.length} proyecto(s) eliminado(s)` });
    rowSelection.value = {};
    await refresh();
  } catch (error) {
    toastApiError(error, { fallback: "Error al eliminar proyectos." });
  }
}
</script>

<template>
  <UDashboardPanel id="projects">
    <template #header>
      <UDashboardNavbar title="Proyectos">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton label="Nuevo proyecto" icon="i-lucide-plus" @click="router.push('/projects/new')" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <ProjectsListToolbar
        v-model:search-query="searchQuery"
        :selected-count="selectedCount"
        :table-api="tableApi"
        @confirm-batch-delete="onBatchDelete"
      />

      <UTable
        ref="table"
        v-model:column-visibility="columnVisibility"
        v-model:row-selection="rowSelection"
        class="shrink-0"
        :data="projects"
        :columns="columns"
        :loading="loading"
        :ui="{
          base: 'table-fixed border-separate border-spacing-0',
          thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
          tbody: '[&>tr]:last:[&>td]:border-b-0',
          th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
          td: 'border-b border-default',
          separator: 'h-0',
        }"
      >
        <template #empty>
          <div class="py-8 text-center text-muted">
            No hay proyectos registrados. Convierte una cotización aceptada o crea uno manualmente.
          </div>
        </template>
      </UTable>

      <div v-if="meta" class="mt-auto flex items-center justify-between gap-3 border-t border-default pt-4">
        <div class="text-sm text-muted">
          {{ selectedCount }} seleccionada(s) en esta pagina ({{ projects.length }} filas)
        </div>

        <UPagination
          :default-page="page"
          :items-per-page="meta.per_page"
          :total="meta.total"
          @update:page="page = $event"
        />
      </div>
    </template>
  </UDashboardPanel>
</template>
