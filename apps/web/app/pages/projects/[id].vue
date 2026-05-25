<script setup lang="ts">
import type { Project, ProjectPayment } from "@freelance/contracts";

definePageMeta({ layout: "default" });

const route = useRoute();
const router = useRouter();
const { find, getPayments } = useProjectsApi();

const projectId = computed(() => Number(route.params.id));

const { data: project, status, refresh } = useAsyncData(
  () => `project-${projectId.value}`,
  () => find(projectId.value),
  { server: false, watch: [projectId] },
);

const { data: paymentsData, refresh: refreshPayments } = useAsyncData<ProjectPayment[]>(
  () => `project-payments-${projectId.value}`,
  async () => {
    const result = await getPayments(projectId.value);
    return result.data;
  },
  { server: false, watch: [projectId] },
);

const payments = computed(() => paymentsData.value ?? []);

async function refreshAll() {
  await refresh();
  await refreshPayments();
}
</script>

<template>
  <UDashboardPanel id="project-detail">
    <template #header>
      <UDashboardNavbar :title="project ? project.name : 'Detalle de proyecto'">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            label="Volver"
            icon="i-lucide-arrow-left"
            variant="ghost"
            @click="router.push('/projects')"
          />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <UCard v-if="status === 'pending'">
        <div class="text-center py-8 text-muted">Cargando...</div>
      </UCard>

      <UCard v-else-if="!project">
        <div class="text-center py-8 text-muted">Proyecto no encontrado.</div>
      </UCard>

      <div v-else>
        <div class="max-w-7xl space-y-6">
          <ProjectsSectionsProjectHeader :project="project" />

          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
              <ProjectsSectionsProjectSummary :project="project" />
            </div>
            <div>
              <ProjectsSectionsProjectPaymentsCard
                :project="project"
                :payments="payments"
                @refresh="refreshAll()"
              />
            </div>
          </div>
        </div>
      </div>
    </template>
  </UDashboardPanel>
</template>
