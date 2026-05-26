<script setup lang="ts">
import { useProjectsApi } from "~/composables/projects/useProjectsApi";
import { useClientsApi } from "~/composables/clients/useClientsApi";
import { useProjectForm } from "~/composables/projects/useProjectForm";
import {
  agreedTotalToCents,
  serializeProjectCreatePayload,
} from "~/composables/projects/projectFormHelpers";

definePageMeta({ layout: "default" });

const { create } = useProjectsApi();
const { list: listClients } = useClientsApi();
const { toastApiError } = useApiError();
const toast = useToast();
const router = useRouter();

const saving = ref(false);
const form = useProjectForm();

const { data: clientsData } = useAsyncData(
  "clients-for-project-select",
  () => listClients(1, ""),
);

const clients = computed(() => clientsData.value?.data ?? []);
const selectedClientName = computed(() => {
  return clients.value.find((client) => client.id === form.clientId.value)?.name;
});
const agreedTotalCentsPreview = computed(() =>
  agreedTotalToCents(form.agreedTotal.value, form.agreedTotalPreview.value),
);

async function onSubmit() {
  const agreedTotalCents = agreedTotalCentsPreview.value;

  if (!form.clientId.value) {
    toast.add({ title: "Selecciona un cliente", color: "warning" });
    return;
  }

  if (!form.name.value.trim()) {
    toast.add({ title: "Ingresa el nombre del proyecto", color: "warning" });
    return;
  }

  if (agreedTotalCents <= 0) {
    toast.add({ title: "Ingresa un total acordado mayor a 0", color: "warning" });
    return;
  }

  saving.value = true;
  try {
    const payload = serializeProjectCreatePayload(
      form.clientId.value,
      {
        name: form.name.value,
        notes: form.notes.value,
        type: form.type.value,
        startedAt: form.startedAt.value,
      },
      agreedTotalCents,
    );
    const project = await create(payload);
    toast.add({ title: "Proyecto creado", color: "success" });
    await router.push(`/projects/${project.id}`);
  } catch (error) {
    toastApiError(error, { fallback: "No se pudo crear el proyecto." });
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <UDashboardPanel id="projects-new">
    <template #header>
      <UDashboardNavbar title="Nuevo proyecto">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton label="Volver" icon="i-lucide-arrow-left" variant="ghost" @click="router.push('/projects')" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="grid w-full max-w-7xl gap-6 xl:grid-cols-[minmax(0,1fr)_22rem] xl:items-start">
        <form class="space-y-6" @submit.prevent="onSubmit">
          <UCard class="w-full">
            <ProjectsUiProjectFormFields
              :clients="clients"
              :client-id="form.clientId.value"
              :name="form.name.value"
              :notes="form.notes.value"
              :type="form.type.value"
              :agreed-total="form.agreedTotal.value"
              :started-at="form.startedAt.value"
              @update:client-id="form.clientId.value = $event"
              @update:name="form.name.value = $event"
              @update:notes="form.notes.value = $event"
              @update:type="form.type.value = $event"
              @update:agreed-total="form.agreedTotal.value = $event"
              @update:agreed-total-preview="form.agreedTotalPreview.value = $event"
              @update:started-at="form.startedAt.value = $event"
            />
          </UCard>

          <div class="flex items-center gap-3">
            <div class="flex-1" />
            <UButton variant="outline" @click="router.push('/projects')">
              Cancelar
            </UButton>
            <UButton type="submit" :loading="saving">
              Guardar proyecto
            </UButton>
          </div>
        </form>

        <ProjectsSectionsProjectDraftSummaryCard
          :client-name="selectedClientName"
          :name="form.name.value"
          :type="form.type.value"
          :started-at="form.startedAt.value"
          :agreed-total-cents="agreedTotalCentsPreview"
        />
      </div>
    </template>
  </UDashboardPanel>
</template>
