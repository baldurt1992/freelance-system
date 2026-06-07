<script setup lang="ts">
  import type { Row } from "@tanstack/table-core";
  import type { Client } from "@freelance/contracts";
  import type { ClientsTableApi, ClientsTableExpose } from "~/types/clients-table";
  import TableEmptyState from "~/components/ui/TableEmptyState.vue";
  import { useClientsApi } from "~/composables/clients/useClientsApi";
  import { useClientsTableColumns } from "~/composables/clients/useClientsTableColumns";

  const toast = useToast();
  const router = useRouter();
  const { toastApiError } = useApiError();
  const table = useTemplateRef<ClientsTableExpose>("table");

  const { list, remove } = useClientsApi();

  const page = ref(1);
  const searchQuery = ref("");
  /** Valor enviado a la API; el input usa searchQuery con debounce para no disparar doble fetch. */
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
    () => `clients-list-${page.value}-${debouncedSearch.value}`,
    () => list(page.value, debouncedSearch.value),
    { watch: [page, debouncedSearch] },
  );

  const clients = computed(() => data.value?.data ?? []);
  const meta = computed(() => data.value?.meta);
  const loading = computed(() => status.value === "pending");

  const columnVisibility = ref();
  const rowSelection = ref<Record<string, boolean>>({});

  function navigateToDetail(id: number) {
    router.push(`/clients/${id}`);
  }

  async function onDelete(row: Row<Client>) {
    try {
      await remove(row.original.id);
      toast.add({ title: "Cliente eliminado" });
      await refresh();
    } catch (error) {
      toastApiError(error, { fallback: "No se pudo eliminar el cliente." });
    }
  }

  const { columns } = useClientsTableColumns({
    onNavigateDetail: navigateToDetail,
    onEdit: (id) => router.push(`/clients/${id}?mode=edit`),
    onDelete,
  });

  function getTableApi(): ClientsTableApi | null {
    return table.value?.tableApi ?? null;
  }

  const selectedCount = computed((): number => {
    return getTableApi()?.getFilteredSelectedRowModel().rows.length ?? 0;
  });

  const tableApi = computed((): ClientsTableApi | null => getTableApi());

  async function onBatchDelete() {
    const rows = getTableApi()?.getFilteredSelectedRowModel().rows ?? [];
    const ids = rows.map((r) => r.original.id);
    if (!ids.length) return;

    try {
      await Promise.all(ids.map((id) => remove(id)));
      toast.add({ title: `${ids.length} cliente(s) eliminado(s)` });
      rowSelection.value = {};
      await refresh();
    } catch (error) {
      toastApiError(error, { fallback: "Error al eliminar clientes." });
    }
  }
</script>

<template>
  <UDashboardPanel id="clients">
    <template #header>
      <UDashboardNavbar title="Clientes">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton label="Nuevo cliente" icon="i-lucide-user-plus" @click="router.push('/clients/new')" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <ClientsListToolbar v-model:search-query="searchQuery" :selected-count="selectedCount" :table-api="tableApi"
        @confirm-batch-delete="onBatchDelete" />

      <UTable ref="table" v-model:column-visibility="columnVisibility" v-model:row-selection="rowSelection"
        class="shrink-0" :data="clients" :columns="columns" :loading="loading" :ui="{
          base: 'table-fixed border-separate border-spacing-0',
          thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
          tbody: '[&>tr]:last:[&>td]:border-b-0',
          th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
          td: 'border-b border-default',
          separator: 'h-0',
        }">
        <template #empty>
          <TableEmptyState message="No hay clientes registrados." />
        </template>
      </UTable>

      <div v-if="meta" class="mt-auto flex items-center justify-between gap-3 border-t border-default pt-4">
        <div class="text-sm text-muted">
          {{ selectedCount }} seleccionada(s) en esta página ({{ clients.length }} filas)
        </div>

        <UPagination :default-page="page" :items-per-page="meta.per_page" :total="meta.total"
          @update:page="page = $event" />
      </div>
    </template>
  </UDashboardPanel>
</template>
