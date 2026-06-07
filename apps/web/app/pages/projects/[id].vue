<script setup lang="ts">
import PageContentWide from "~/components/ui/PageContentWide.vue";
import PageStateCard from "~/components/ui/PageStateCard.vue";
import type { Project, ProjectPayment, BillingDocument } from "@freelance/contracts";

definePageMeta({ layout: "default" });

const route = useRoute();
const router = useRouter();
const { find, getPayments } = useProjectsApi();
const { listByProject, downloadPdf } = useBillingApi();
const { toastApiError } = useApiError();

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

const { data: billingData, refresh: refreshBilling } = useAsyncData<BillingDocument[]>(
  () => `project-billing-${projectId.value}`,
  async () => {
    const result = await listByProject(projectId.value);
    return result.data;
  },
  { server: false, watch: [projectId] },
);

const payments = computed(() => paymentsData.value ?? []);
const billingDocuments = computed(() => billingData.value ?? []);

async function refreshAll() {
  await Promise.all([refresh(), refreshPayments(), refreshBilling()]);
}

async function onDownloadBilling(billingDocumentId: number) {
  try {
    const blob = await downloadPdf(billingDocumentId);
    const doc = billingDocuments.value.find((item) => item.id === billingDocumentId);
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `${doc?.number ?? "cuenta-cobro"}.pdf`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  } catch (error) {
    toastApiError(error, { fallback: "No se pudo descargar la cuenta de cobro." });
  }
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
      <PageStateCard v-if="status === 'pending'" message="Cargando..." />

      <PageStateCard v-else-if="!project" message="Proyecto no encontrado." />

      <div v-else>
        <PageContentWide class="space-y-6">
          <ProjectsSectionsProjectHeader :project="project" />

          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
              <ProjectsSectionsProjectSummary :project="project" />
              <ProjectsSectionsProjectDocuments
                :project="project"
                :billing-documents="billingDocuments"
                @download-billing="onDownloadBilling"
              />
            </div>
            <div class="space-y-6">
              <ProjectsSectionsProjectPaymentsCard
                :project="project"
                :payments="payments"
                @refresh="refreshAll()"
              />
              <ProjectsSectionsProjectActionsCard
                :project="project"
                @completed="refreshAll()"
              />
            </div>
          </div>
        </PageContentWide>
      </div>
    </template>
  </UDashboardPanel>
</template>
