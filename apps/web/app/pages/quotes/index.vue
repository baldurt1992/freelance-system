<script setup lang="ts">
import type { Row } from "@tanstack/table-core";
import type { Quote } from "@freelance/contracts";
import type { QuotesTableApi, QuotesTableExpose } from "~/types/quotes-table";
import TableEmptyState from "~/components/ui/TableEmptyState.vue";
import { useQuotesApi } from "~/composables/quotes/useQuotesApi";
import { useQuotesTableColumns } from "~/composables/quotes/useQuotesTableColumns";

const toast = useToast();
const router = useRouter();
const { toastApiError } = useApiError();
const table = useTemplateRef<QuotesTableExpose>("table");

const { list, remove } = useQuotesApi();

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
  () => `quotes-list-${page.value}-${debouncedSearch.value}`,
  () => list(page.value, debouncedSearch.value),
  { watch: [page, debouncedSearch] },
);

const quotes = computed(() => data.value?.data ?? []);
const meta = computed(() => data.value?.meta);
const loading = computed(() => status.value === "pending");

const columnVisibility = ref();
const rowSelection = ref<Record<string, boolean>>({});

function navigateToDetail(id: number) {
  router.push(`/quotes/${id}`);
}

async function onDelete(row: Row<Quote>) {
  try {
    await remove(row.original.id);
    toast.add({ title: "Cotización eliminada" });
    await refresh();
  } catch (error) {
    toastApiError(error, { fallback: "No se pudo eliminar la cotización." });
  }
}

const { columns } = useQuotesTableColumns({
  onNavigateDetail: navigateToDetail,
  onDelete,
});

function getTableApi(): QuotesTableApi | null {
  return table.value?.tableApi ?? null;
}

const selectedCount = computed((): number => {
  return getTableApi()?.getFilteredSelectedRowModel().rows.length ?? 0;
});

const tableApi = computed((): QuotesTableApi | null => getTableApi());

async function onBatchDelete() {
  const rows = getTableApi()?.getFilteredSelectedRowModel().rows ?? [];
  const ids = rows.map((r) => r.original.id);
  if (!ids.length) return;

  try {
    await Promise.all(ids.map((id) => remove(id)));
    toast.add({ title: `${ids.length} cotización(es) eliminada(s)` });
    rowSelection.value = {};
    await refresh();
  } catch (error) {
    toastApiError(error, { fallback: "Error al eliminar cotizaciones." });
  }
}
</script>

<template>
  <UDashboardPanel id="quotes">
    <template #header>
      <UDashboardNavbar title="Cotizaciones">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton label="Nueva cotización" icon="i-lucide-file-plus" @click="router.push('/quotes/new')" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <QuotesListToolbar
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
        :data="quotes"
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
          <TableEmptyState message="No hay cotizaciones registradas." />
        </template>
      </UTable>

      <div v-if="meta" class="mt-auto flex items-center justify-between gap-3 border-t border-default pt-4">
        <div class="text-sm text-muted">
          {{ selectedCount }} seleccionada(s) en esta página ({{ quotes.length }} filas)
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
