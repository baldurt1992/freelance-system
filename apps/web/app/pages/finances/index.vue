<script setup lang="ts">
import { CalendarDate } from "@internationalized/date";
import { formatMoney } from "~/utils/formatMoney";
import { useFinancesApi } from "~/composables/finances/useFinancesApi";
import { useFinanceSummary } from "~/composables/finances/useFinanceSummary";

definePageMeta({ layout: "default" });

const router = useRouter();
const { toastApiError } = useApiError();
const { getSummary, listEntries, removeEntry } = useFinancesApi();
const { monthInputDefault, getNetLabelText } = useFinanceSummary();

const month = ref(monthInputDefault());
const tab = ref<"summary" | "income" | "expense">("summary");
const page = ref(1);
const loadingDelete = ref<number | null>(null);

type InputDateExpose = {
  inputsRef?: Array<{ $el?: HTMLElement } | HTMLElement | null>;
};

const inputDate = useTemplateRef<InputDateExpose>("inputDate");

const { data: summary, refresh: refreshSummary } = useAsyncData(
  () => `finances-summary-${month.value}`,
  () => getSummary(month.value),
  { watch: [month] },
);

const activeType = computed<"income" | "expense">(() => (tab.value === "expense" ? "expense" : "income"));

watch(tab, () => {
  page.value = 1;
});

const { data: entriesData, status, refresh: refreshEntries } = useAsyncData(
  () => `finances-entries-${tab.value}-${month.value}-${page.value}`,
  () => listEntries(page.value, tab.value === "summary" ? { month: month.value } : { month: month.value, type: activeType.value }),
  { watch: [tab, month, page] },
);

const entries = computed(() => entriesData.value?.data ?? []);
const meta = computed(() => entriesData.value?.meta);
const loading = computed(() => status.value === "pending");

function parseMonthToCalendarDate(value: string): CalendarDate | undefined {
  const parts = value.split("-").map((segment) => parseInt(segment, 10));
  if (parts.length !== 2 || parts.some((part) => Number.isNaN(part))) return undefined;

  const [year, monthValue] = parts as [number, number];
  return new CalendarDate(year, monthValue, 1);
}

function calendarDateToMonth(value: CalendarDate | null | undefined): string {
  if (!value) return monthInputDefault();

  const year = String(value.year).padStart(4, "0");
  const monthValue = String(value.month).padStart(2, "0");
  return `${year}-${monthValue}`;
}

function getInputDateReference(): HTMLElement | undefined {
  const candidate = inputDate.value?.inputsRef?.[3];
  if (!candidate) return undefined;
  return candidate instanceof HTMLElement ? candidate : candidate.$el;
}

async function onDelete(entryId: number) {
  loadingDelete.value = entryId;
  try {
    await removeEntry(entryId);
    await Promise.all([refreshEntries(), refreshSummary()]);
  } catch (error) {
    toastApiError(error, { fallback: "No se pudo eliminar el movimiento." });
  } finally {
    loadingDelete.value = null;
  }
}
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
      <div class="flex flex-wrap items-center gap-3">
        <UTabs
          v-model="tab"
          :items="[
            { value: 'summary', label: 'Resumen' },
            { value: 'income', label: 'Ingresos' },
            { value: 'expense', label: 'Gastos' },
          ]"
        />

        <div class="ms-auto">
          <UInputDate
            ref="inputDate"
            id="finance-month"
            name="month"
            class="w-full min-w-44"
            :model-value="parseMonthToCalendarDate(month)"
            @update:model-value="month = calendarDateToMonth($event as CalendarDate | undefined)"
          >
            <template #trailing>
              <UPopover :reference="getInputDateReference()">
                <UButton
                  color="neutral"
                  variant="link"
                  size="sm"
                  icon="i-lucide-calendar"
                  aria-label="Seleccionar mes"
                  class="px-0"
                />

                <template #content>
                  <UCalendar
                    :model-value="parseMonthToCalendarDate(month)"
                    class="p-2"
                    @update:model-value="month = calendarDateToMonth($event as CalendarDate | undefined)"
                  />
                </template>
              </UPopover>
            </template>
          </UInputDate>
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
            {{ tab === 'summary' ? 'Movimientos del mes' : tab === 'income' ? 'Ingresos' : 'Gastos' }}
          </h3>
        </template>

        <UTable
          :data="entries"
          :loading="loading"
          :columns="[
            { accessorKey: 'occurred_on', header: 'Fecha' },
            { accessorKey: 'description', header: 'Descripción' },
            { accessorKey: 'category', header: 'Categoría' },
            { accessorKey: 'amount_cents', header: 'Monto' },
            { accessorKey: 'type', header: 'Tipo' },
            { accessorKey: 'is_manual', header: 'Origen' },
            { id: 'actions', header: '' },
          ]"
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

          <template #actions-cell="{ row }">
            <div class="flex justify-end gap-2">
              <UButton
                v-if="row.original.is_manual"
                icon="i-lucide-pencil"
                color="neutral"
                variant="ghost"
                @click="router.push(`/finances/entries/${row.original.id}/edit`)"
              />
              <UButton
                v-if="row.original.is_manual"
                icon="i-lucide-trash"
                color="error"
                variant="ghost"
                :loading="loadingDelete === row.original.id"
                @click="onDelete(row.original.id)"
              />
            </div>
          </template>

          <template #empty>
            <div class="py-8 text-center text-muted">No hay movimientos para este filtro.</div>
          </template>
        </UTable>

        <div v-if="meta" class="mt-4 flex justify-end">
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
