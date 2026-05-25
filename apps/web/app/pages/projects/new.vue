<script setup lang="ts">
import type { ProjectType } from "@freelance/contracts";
import { CalendarDate } from "@internationalized/date";
import { useProjectsApi } from "~/composables/projects/useProjectsApi";
import { useClientsApi } from "~/composables/clients/useClientsApi";
import { parseLocalizedNumber } from "~/utils/parseLocalizedNumber";

definePageMeta({ layout: "default" });

const { create } = useProjectsApi();
const { list: listClients } = useClientsApi();
const { toastApiError } = useApiError();
const toast = useToast();
const router = useRouter();

const saving = ref(false);
const clientId = ref<number | undefined>();
const name = ref("");
const notes = ref("");
const type = ref<ProjectType>("freelance");
const agreedTotal = ref<number | undefined>();
const agreedTotalPreview = ref<number | undefined>();
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
const selectedClientName = computed(() => {
  return clients.value.find((client) => client.id === clientId.value)?.name;
});
const agreedTotalCentsPreview = computed(() => {
  const value = agreedTotalPreview.value ?? agreedTotal.value;
  if (!value || !Number.isFinite(value) || value <= 0) return 0;
  return Math.round(value * 100);
});

type InputDateExpose = {
  inputsRef?: Array<{ $el?: HTMLElement } | HTMLElement | null>;
};

const inputDate = useTemplateRef<InputDateExpose>("inputDate");

function getInputDateReference(): HTMLElement | undefined {
  const candidate = inputDate.value?.inputsRef?.[3];
  if (!candidate) return undefined;
  return candidate instanceof HTMLElement ? candidate : candidate.$el;
}

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

function updateAgreedTotalFromInput(value: string): void {
  agreedTotalPreview.value = parseLocalizedNumber(value) ?? undefined;
}

async function onSubmit() {
  const agreedTotalValue = agreedTotalPreview.value ?? agreedTotal.value;

  if (!clientId.value) {
    toast.add({ title: "Selecciona un cliente", color: "warning" });
    return;
  }

  if (!name.value.trim()) {
    toast.add({ title: "Ingresa el nombre del proyecto", color: "warning" });
    return;
  }

  if (!agreedTotalValue || !Number.isFinite(agreedTotalValue) || agreedTotalValue <= 0) {
    toast.add({ title: "Ingresa un total acordado mayor a 0", color: "warning" });
    return;
  }

  saving.value = true;
  try {
    const project = await create({
      client_id: clientId.value,
      name: name.value.trim(),
      notes: notes.value.trim() || null,
      type: type.value,
      agreed_total_cents: Math.round(agreedTotalValue * 100),
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
      <div class="grid w-full max-w-7xl gap-6 xl:grid-cols-[minmax(0,1fr)_22rem] xl:items-start">
        <form class="space-y-6" @submit.prevent="onSubmit">
          <UCard class="w-full">
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

              <UFormField label="Total acordado" name="agreed_total_cents" required>
                <UInputNumber
                  id="project-agreed-total"
                  v-model="agreedTotal"
                  name="agreed_total_cents"
                  :min="0.01"
                  :step="0.01"
                  :increment="false"
                  :decrement="false"
                  class="w-full"
                  placeholder="Ej. 2500000"
                  @input="updateAgreedTotalFromInput(($event.target as HTMLInputElement).value)"
                  @update:model-value="agreedTotalPreview = undefined"
                />
              </UFormField>

              <UFormField label="Fecha de inicio" name="started_at">
                <UInputDate
                  ref="inputDate"
                  id="project-started-at"
                  name="started_at"
                  class="w-full"
                  :model-value="parseDateToCalendarDate(startedAt)"
                  @update:model-value="startedAt = calendarDateToString($event as CalendarDate | undefined)"
                >
                  <template #trailing>
                    <UPopover :reference="getInputDateReference()">
                      <UButton
                        color="neutral"
                        variant="link"
                        size="sm"
                        icon="i-lucide-calendar"
                        aria-label="Seleccionar fecha"
                        class="px-0"
                      />

                      <template #content>
                        <UCalendar
                          :model-value="parseDateToCalendarDate(startedAt)"
                          class="p-2"
                          @update:model-value="startedAt = calendarDateToString($event as CalendarDate | undefined)"
                        />
                      </template>
                    </UPopover>
                  </template>
                </UInputDate>
              </UFormField>

              <UFormField label="Alcance / notas" name="notes" class="md:col-span-2">
                <UTextarea
                  id="project-notes"
                  v-model="notes"
                  name="notes"
                  class="w-full"
                  placeholder="Describe el alcance del proyecto, entregables o condiciones importantes."
                />
              </UFormField>
            </div>

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
          :name="name"
          :type="type"
          :started-at="startedAt"
          :agreed-total-cents="agreedTotalCentsPreview"
        />
      </div>
    </template>
  </UDashboardPanel>
</template>
