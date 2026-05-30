<script setup lang="ts">
import FinancesUiMonthPicker from "~/components/finances/ui/MonthPicker.vue";
import { useDashboardApi } from "~/composables/dashboard/useDashboardApi";
import { useFinanceSummary } from "~/composables/finances/useFinanceSummary";

const tenant = useTenantStore();
const { getDashboard } = useDashboardApi();
const { monthInputDefault } = useFinanceSummary();

const month = ref(monthInputDefault());

const { data, status, error } = useAsyncData(
  () => `dashboard-${month.value}`,
  () => getDashboard(month.value),
  { watch: [month] },
);

const dashboard = computed(() => data.value);
const loading = computed(() => status.value === "pending");
</script>

<template>
  <UDashboardPanel id="home">
    <template #header>
      <UDashboardNavbar title="Inicio">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="space-y-6">
        <div class="flex flex-wrap items-center gap-3">
          <div class="space-y-1">
            <p class="text-sm text-muted">Workspace</p>
            <h2 class="text-2xl font-semibold text-highlighted">
              {{ tenant.displayName }}
            </h2>
          </div>

          <div class="ms-auto">
            <FinancesUiMonthPicker
              :month="month"
              :month-fallback="monthInputDefault()"
              @update:month="month = $event"
            />
          </div>
        </div>

        <DashboardSectionsDashboardKpis
          v-if="dashboard"
          :kpis="dashboard.kpis"
          :currency="tenant.currency"
        />

        <UPageGrid class="gap-4 sm:gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(0,1fr)]">
          <DashboardSectionsDashboardFinancialSummary
            v-if="dashboard"
            :summary="dashboard.financial_summary"
            :currency="tenant.currency"
            :month="dashboard.month"
          />

          <DashboardSectionsDashboardPendingItems
            v-if="dashboard"
            :pending="dashboard.pending"
          />
        </UPageGrid>

        <DashboardSectionsDashboardRecentActivity
          v-if="dashboard"
          :items="dashboard.recent_activity"
        />

        <UAlert
          v-if="error"
          color="error"
          variant="subtle"
          icon="i-lucide-alert-circle"
          title="No se pudo cargar el dashboard"
          description="Intenta de nuevo en unos segundos o revisa la conectividad con la API."
        />

        <div
          v-if="loading && !dashboard"
          class="rounded-lg border border-dashed border-default px-4 py-8 text-center text-sm text-muted"
        >
          Cargando dashboard...
        </div>
      </div>
    </template>
  </UDashboardPanel>
</template>
