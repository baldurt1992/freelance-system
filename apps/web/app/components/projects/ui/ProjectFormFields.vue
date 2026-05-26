<script setup lang="ts">
import type { Client, ProjectType } from "@freelance/contracts";
import { CalendarDate } from "@internationalized/date";
import {
  calendarDateToString,
  parseDateToCalendarDate,
  projectTypeOptions,
} from "~/composables/projects/projectFormHelpers";
import { parseLocalizedNumber } from "~/utils/parseLocalizedNumber";

const props = defineProps<{
  clients: Client[];
  clientId?: number;
  name: string;
  notes: string;
  type: ProjectType;
  agreedTotal?: number;
  startedAt?: string;
}>();

const emit = defineEmits<{
  "update:clientId": [value: number | undefined];
  "update:name": [value: string];
  "update:notes": [value: string];
  "update:type": [value: ProjectType];
  "update:agreedTotal": [value: number | undefined];
  "update:agreedTotalPreview": [value: number | undefined];
  "update:startedAt": [value: string | undefined];
}>();

type InputDateExpose = {
  inputsRef?: Array<{ $el?: HTMLElement } | HTMLElement | null>;
};

const inputDate = useTemplateRef<InputDateExpose>("inputDate");

function getInputDateReference(): HTMLElement | undefined {
  const candidate = inputDate.value?.inputsRef?.[3];
  if (!candidate) return undefined;
  return candidate instanceof HTMLElement ? candidate : candidate.$el;
}

function updateAgreedTotalFromInput(value: string): void {
  emit("update:agreedTotalPreview", parseLocalizedNumber(value) ?? undefined);
}
</script>

<template>
  <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <UFormField label="Cliente" name="client_id" required>
      <USelect
        id="project-client"
        name="client_id"
        :model-value="clientId"
        :items="clients.map((c) => ({ label: c.name, value: c.id }))"
        placeholder="Seleccionar cliente..."
        class="w-full"
        @update:model-value="emit('update:clientId', $event as number | undefined)"
      />
    </UFormField>

    <UFormField label="Tipo" name="type" required>
      <USelect
        id="project-type"
        name="type"
        :model-value="type"
        :items="[...projectTypeOptions]"
        class="w-full"
        @update:model-value="emit('update:type', ($event as ProjectType | undefined) ?? 'freelance')"
      />
    </UFormField>

    <UFormField label="Nombre del proyecto" name="name" required class="md:col-span-2">
      <UInput
        id="project-name"
        name="name"
        :model-value="name"
        class="w-full"
        placeholder="Ej. Sitio web corporativo ACME"
        autocomplete="off"
        @update:model-value="emit('update:name', $event)"
      />
    </UFormField>

    <UFormField label="Total acordado" name="agreed_total_cents" required>
      <UInputNumber
        id="project-agreed-total"
        :model-value="agreedTotal"
        name="agreed_total_cents"
        :min="0.01"
        :step="0.01"
        :increment="false"
        :decrement="false"
        class="w-full"
        placeholder="Ej. 2500000"
        @input="updateAgreedTotalFromInput(($event.target as HTMLInputElement).value)"
        @update:model-value="emit('update:agreedTotal', $event); emit('update:agreedTotalPreview', undefined)"
      />
    </UFormField>

    <UFormField label="Fecha de inicio" name="started_at">
      <UInputDate
        ref="inputDate"
        id="project-started-at"
        name="started_at"
        class="w-full"
        :model-value="parseDateToCalendarDate(startedAt)"
        @update:model-value="emit('update:startedAt', calendarDateToString($event as CalendarDate | undefined))"
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
                @update:model-value="emit('update:startedAt', calendarDateToString($event as CalendarDate | undefined))"
              />
            </template>
          </UPopover>
        </template>
      </UInputDate>
    </UFormField>

    <UFormField label="Alcance / notas" name="notes" class="md:col-span-2">
      <UTextarea
        id="project-notes"
        :model-value="notes"
        name="notes"
        class="w-full"
        placeholder="Describe el alcance del proyecto, entregables o condiciones importantes."
        @update:model-value="emit('update:notes', $event)"
      />
    </UFormField>
  </div>
</template>
