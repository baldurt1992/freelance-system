<script setup lang="ts">
import type { ProjectType } from "@freelance/contracts";
import { CalendarDate } from "@internationalized/date";
import { useProjectsApi } from "~/composables/projects/useProjectsApi";
import { useClientsApi } from "~/composables/clients/useClientsApi";

definePageMeta({ layout: "default" });

const { create } = useProjectsApi();
const { list: listClients } = useClientsApi();
const { toastApiError } = useApiError();
const toast = useToast();
const router = useRouter();

const saving = ref(false);
const clientId = ref<number | undefined>();
const name = ref("");
const type = ref<ProjectType>("freelance");
const startedAt = ref<string | undefined>();

const typeOptions = [
  { label: "Freelance", value: "freelance" },
  { label: "Precio fijo", value: "fixed" },
  { label: "Retainer", value: "retainer" },
] as const;

const { data: clientsData } = useAsyncData(
  "clients-for-project-select",
  () => listClients(1, ""),
);

const clients = computed(() => clientsData.value?.data ?? []);

function calendarDateToString(date: CalendarDate | null | undefined): string | undefined {
  if (!date) return undefined;
  const y = String(date.year).padStart(4, "0");
  const m = String(date.month).padStart(2, "0");
  const d = String(date.day).padStart(2, "0");
  return `${y}-${m}-${d}`;
}

function parseDateToCalendarDate(value?: string): CalendarDate | undefined {
  if (!value) return undefined;
  const parts = value.split("-").map((v) => parseInt(v, 10));
  if (parts.length !== 3 || parts.some((n) => Number.isNaN(n))) return undefined;
  const [year, month, day] = parts as [number, number, number];
  return new CalendarDate(year, month, day);
}

async function onSubmit() {
  if (!clientId.value) {
    toast.add({ title: "Selecciona un cliente", color: "warning" });
    return;
  }

  if (!name.value.trim()) {
    toast.add({ title: "Ingresa el nombre del proyecto", color: "warning" });
    return;
  }

  saving.value = true;
  try {
    const project = await create({
      client_id: clientId.value,
      name: name.value.trim(),
      type: type.value,
      started_at: startedAt.value ?? null,
    });
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
      <UCard class="w-full max-w-3xl">
        <form class="space-y-5" @submit.prevent="onSubmit">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <UFormField label="Cliente" name="client_id" required>
              <USelect
                id="project-client"
                name="client_id"
                :model-value="clientId"
                :items="clients.map((c) => ({ label: c.name, value: c.id }))"
                placeholder="Seleccionar cliente..."
                class="w-full"
                @update:model-value="clientId = $event as number | undefined"
              />
            </UFormField>

            <UFormField label="Tipo" name="type" required>
              <USelect
                id="project-type"
                name="type"
                :model-value="type"
                :items="[...typeOptions]"
                class="w-full"
                @update:model-value="type = ($event as ProjectType | undefined) ?? 'freelance'"
              />
            </UFormField>

            <UFormField label="Nombre del proyecto" name="name" required class="md:col-span-2">
              <UInput
                id="project-name"
                name="name"
                v-model="name"
                class="w-full"
                placeholder="Ej. Sitio web corporativo ACME"
                autocomplete="off"
              />
            </UFormField>

            <UFormField label="Fecha de inicio" name="started_at">
              <UInputDate
                id="project-started-at"
                name="started_at"
                class="w-full"
                :model-value="parseDateToCalendarDate(startedAt)"
                @update:model-value="startedAt = calendarDateToString($event as CalendarDate | undefined)"
              />
            </UFormField>
          </div>

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
      </UCard>
    </template>
  </UDashboardPanel>
</template>
