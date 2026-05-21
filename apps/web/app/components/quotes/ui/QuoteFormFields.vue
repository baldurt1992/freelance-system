<script setup lang="ts">
  import { CalendarDate } from "@internationalized/date";
  import type { Client } from "@freelance/contracts";

  const props = defineProps<{
    clients: Client[];
    clientId?: number;
    title: string | null;
    notes: string | null;
    validUntil?: string;
  }>();

  const emit = defineEmits<{
    "update:clientId": [value: number | undefined];
    "update:title": [value: string | null];
    "update:notes": [value: string | null];
    "update:validUntil": [value: string | undefined];
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

  function parseDateToCalendarDate(value?: string): CalendarDate | undefined {
    if (!value) return undefined;
    const parts = value.split("-").map((v) => parseInt(v, 10));
    if (parts.length !== 3 || parts.some((n) => Number.isNaN(n))) return undefined;
    const [year, month, day] = parts as [number, number, number];
    return new CalendarDate(year, month, day);
  }

  function calendarDateToString(date: CalendarDate | null | undefined): string | undefined {
    if (!date) return undefined;
    const y = String(date.year).padStart(4, "0");
    const m = String(date.month).padStart(2, "0");
    const d = String(date.day).padStart(2, "0");
    return `${y}-${m}-${d}`;
  }
</script>

<template>
  <div class="grid w-full grid-cols-1 gap-4 md:grid-cols-2">
    <UFormField label="Cliente" name="client_id" required>
      <USelect id="quote-client" name="client_id" :model-value="clientId" class="w-full"
        :items="clients.map((c) => ({ label: c.name, value: c.id }))" placeholder="Seleccionar cliente..."
        @update:model-value="emit('update:clientId', $event as number | undefined)" />
    </UFormField>

    <UFormField label="Título" name="title">
      <UInput id="quote-title" name="title" :model-value="title ?? ''" class="w-full" placeholder="Ej. Proyecto Web"
        autocomplete="off" @update:model-value="emit('update:title', $event || null)" />
    </UFormField>

    <UFormField label="Válida hasta" name="valid_until">
      <UInputDate ref="inputDate" id="quote-valid-until" name="valid_until" class="w-full"
        :model-value="parseDateToCalendarDate(validUntil)"
        @update:model-value="emit('update:validUntil', calendarDateToString($event as CalendarDate | undefined))">
        <template #trailing>
          <UPopover :reference="getInputDateReference()">
            <UButton color="neutral" variant="link" size="sm" icon="i-lucide-calendar" aria-label="Seleccionar fecha"
              class="px-0" />

            <template #content>
              <UCalendar :model-value="parseDateToCalendarDate(validUntil)" class="p-2"
                @update:model-value="emit('update:validUntil', calendarDateToString($event as CalendarDate | undefined))" />
            </template>
          </UPopover>
        </template>
      </UInputDate>
    </UFormField>

    <UFormField label="Notas" name="notes" class="md:col-span-2">
      <UTextarea id="quote-notes" name="notes" :model-value="notes ?? ''" class="w-full"
        placeholder="Condiciones, alcance, etc." @update:model-value="emit('update:notes', $event || null)" />
    </UFormField>
  </div>
</template>
