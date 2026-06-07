<script setup lang="ts">
import type { Row } from "@tanstack/table-core";
import type { FinanceEntry } from "@freelance/contracts";
import type { FinancesTableApi, FinancesTableExpose } from "~/types/finances-table";
import FinancesListToolbar from "~/components/finances/FinancesListToolbar.vue";
import FinancesUiMonthPicker from "~/components/finances/ui/MonthPicker.vue";
import TableEmptyState from "~/components/ui/TableEmptyState.vue";
import { formatMoney } from "~/utils/formatMoney";
import { useFinancesApi } from "~/composables/finances/useFinancesApi";
import { useFinanceSummary } from "~/composables/finances/useFinanceSummary";
import { useFinancesListState } from "~/composables/finances/useFinancesListState";
import {
  financesTabItems,
  useFinancesTableColumns,
} from "~/composables/finances/useFinancesTableColumns";

definePageMeta({ layout: "default" });

const router = useRouter();
const { toastApiError } = useApiError();
const { getSummary, listEntries, removeEntry } = useFinancesApi();
const { getNetLabelText } = useFinanceSummary();
const { month, tab, page, activeType, monthInputDefault } = useFinancesListState();
const searchQuery = ref("");
const debouncedSearch = ref("");
const columnVisibility = ref();
const table = useTemplateRef<FinancesTableExpose>("table");

watchDebounced(
  searchQuery,
  (value) => {
    debouncedSearch.value = value;
    page.value = 1;
  },
  { debounce: 300 },
);

function navigateToDetail(id: number) {
  router.push(`/finances/entries/${id}`);
}

async function onDelete(row: Row<FinanceEntry>) {
  loadingDelete.value = row.original.id;
  try {
    await removeEntry(row.original.id);
    await Promise.all([refreshEntries(), refreshSummary()]);
  } catch (error) {
    toastApiError(error, { fallback: "No se pudo eliminar el movimiento." });
  } finally {
    loadingDelete.value = null;
  }
}

const { columns, entriesTableTitle } = useFinancesTableColumns({
  onNavigateDetail: navigateToDetail,
  onEdit: (id) => router.push(`/finances/entries/${id}/edit`),
  onDelete,
});

const loadingDelete = ref<number | null>(null);

const { data: summary, refresh: refreshSummary } = useAsyncData(
  () => `finances-summary-${month.value}`,
  () => getSummary(month.value),
  { watch: [month] },
);

const { data: entriesData, status, refresh: refreshEntries } = useAsyncData(
  () => `finances-entries-${tab.value}-${month.value}-${page.value}`,
  () => listEntries(
    page.value,
    tab.value === "summary"
      ? { month: month.value, search: debouncedSearch.value }
      : { month: month.value, type: activeType.value, search: debouncedSearch.value },
  ),
  { watch: [tab, month, page, debouncedSearch] },
);

const entries = computed(() => entriesData.value?.data ?? []);
const meta = computed(() => entriesData.value?.meta);
const loading = computed(() => status.value === "pending");
const tableApi = computed((): FinancesTableApi | null => table.value?.tableApi ?? null);
</script>

<template>
  <UDashboardPanel id="finances">
    <template #header>
      <UDashboardNavbar title="Finanzas">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            label="Nuevo movimiento"
            icon="i-lucide-plus"
            @click="router.push(`/finances/entries/new?type=${tab === 'expense' ? 'expense' : 'income'}`)"
          />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <FinancesListToolbar v-model:search-query="searchQuery" :table-api="tableApi" />

      <div class="flex flex-wrap items-center gap-3">
        <UTabs v-model="tab" :items="[...financesTabItems]" />

        <div class="ms-auto">
          <FinancesUiMonthPicker
            :month="month"
            :month-fallback="monthInputDefault()"
            @update:month="month = $event"
          />
        </div>
      </div>

      <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
        <UCard>
          <p class="text-sm text-muted">Ingresos</p>
          <p class="text-xl font-semibold text-success">
            {{ formatMoney(summary?.total_income_cents ?? 0, 'COP') }}
          </p>
        </UCard>
        <UCard>
          <p class="text-sm text-muted">Gastos</p>
          <p class="text-xl font-semibold text-error">
            {{ formatMoney(summary?.total_expense_cents ?? 0, 'COP') }}
          </p>
        </UCard>
        <UCard>
          <p class="text-sm text-muted">Balance</p>
          <p class="text-xl font-semibold" :class="(summary?.net_cents ?? 0) >= 0 ? 'text-success' : 'text-error'">
            {{ formatMoney(summary?.net_cents ?? 0, 'COP') }}
          </p>
          <p class="text-xs text-muted">{{ getNetLabelText(summary?.label ?? 'balanced') }}</p>
        </UCard>
      </div>

      <UCard class="mt-4">
        <template #header>
          <h3 class="font-semibold">
            {{ entriesTableTitle(tab) }}
          </h3>
        </template>

        <UTable
          ref="table"
          v-model:column-visibility="columnVisibility"
          class="shrink-0"
          :data="entries"
          :loading="loading"
          :columns="columns"
          :ui="{
            base: 'table-fixed border-separate border-spacing-0',
            thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
            tbody: '[&>tr]:last:[&>td]:border-b-0',
            th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
            td: 'border-b border-default',
            separator: 'h-0',
          }"
        >
          <template #amount_cents-cell="{ row }">
            {{ formatMoney(row.original.amount_cents, 'COP') }}
          </template>

          <template #type-cell="{ row }">
            <UBadge
              :label="row.original.type === 'income' ? 'Ingreso' : 'Gasto'"
              :color="row.original.type === 'income' ? 'success' : 'error'"
              variant="subtle"
            />
          </template>

          <template #is_manual-cell="{ row }">
            <UBadge
              :label="row.original.is_manual ? 'Manual' : 'Automático'"
              :color="row.original.is_manual ? 'info' : 'neutral'"
              variant="subtle"
            />
          </template>

          <template #empty>
            <TableEmptyState message="No hay movimientos para este filtro." />
          </template>
        </UTable>

        <div v-if="meta" class="mt-4 flex items-center justify-end gap-3 border-t border-default pt-4">
          <UPagination
            :default-page="page"
            :items-per-page="meta.per_page"
            :total="meta.total"
            @update:page="page = $event"
          />
        </div>
      </UCard>
    </template>
  </UDashboardPanel>
</template>
