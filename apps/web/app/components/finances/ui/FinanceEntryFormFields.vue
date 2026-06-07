<script setup lang="ts">
import type { FinanceCategory, FinanceEntryType } from "@freelance/contracts";
import { CalendarDate } from "@internationalized/date";
import FinanceCategoryCreateButton from "~/components/finances/ui/FinanceCategoryCreateButton.vue";
import FinanceCategoryDeleteButton from "~/components/finances/ui/FinanceCategoryDeleteButton.vue";
import FinanceCategoryEditButton from "~/components/finances/ui/FinanceCategoryEditButton.vue";
import FinanceCategorySelect from "~/components/finances/ui/FinanceCategorySelect.vue";

const props = defineProps<{
  type: FinanceEntryType;
  amount: number | null;
  occurredOn: string;
  name: string;
  description: string;
  categoryId: number | null;
  categories: FinanceCategory[];
  createFinanceCategory: (input: { type: FinanceEntryType; name: string }) => Promise<FinanceCategory>;
  updateFinanceCategory: (id: number, input: { name: string }) => Promise<FinanceCategory>;
  deleteFinanceCategory: (id: number) => Promise<void>;
}>();

const emit = defineEmits<{
  "update:type": [value: FinanceEntryType];
  "update:amount": [value: number | null];
  "update:occurredOn": [value: string];
  "update:name": [value: string];
  "update:description": [value: string];
  "update:categoryId": [value: number | null];
}>();

type InputDateExpose = {
  inputsRef?: Array<{ $el?: HTMLElement } | HTMLElement | null>;
};

const inputDate = useTemplateRef<InputDateExpose>("inputDate");

const selectedCategory = computed(() => props.categories.find((item) => item.id === props.categoryId) ?? null);

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

function getInputDateReference(): HTMLElement | undefined {
  const candidate = inputDate.value?.inputsRef?.[3];
  if (!candidate) return undefined;
  return candidate instanceof HTMLElement ? candidate : candidate.$el;
}

function clearCategoryIfDeleted(id: number): void {
  if (props.categoryId === id) {
    emit("update:categoryId", null);
  }
}
</script>

<template>
  <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <UFormField label="Tipo" name="type" required>
      <USelect
        id="finance-type"
        name="type"
        :model-value="type"
        :items="[{ label: 'Ingreso', value: 'income' }, { label: 'Gasto', value: 'expense' }]"
        @update:model-value="emit('update:type', (($event as FinanceEntryType | undefined) ?? 'income'))"
      />
    </UFormField>

    <UFormField label="Fecha" name="occurred_on" required>
      <UInputDate
        ref="inputDate"
        id="finance-occurred-on"
        name="occurred_on"
        class="w-full"
        :model-value="parseDateToCalendarDate(occurredOn)"
        @update:model-value="emit('update:occurredOn', calendarDateToString($event as CalendarDate | undefined) ?? occurredOn)"
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
                :model-value="parseDateToCalendarDate(occurredOn)"
                class="p-2"
                @update:model-value="emit('update:occurredOn', calendarDateToString($event as CalendarDate | undefined) ?? occurredOn)"
              />
            </template>
          </UPopover>
        </template>
      </UInputDate>
    </UFormField>

    <UFormField label="Nombre" name="name" required class="md:col-span-2">
      <UInput
        id="finance-name"
        :model-value="name"
        name="name"
        class="w-full"
        placeholder="Ej. Pago hosting junio"
        autocomplete="off"
        @update:model-value="emit('update:name', $event)"
      />
    </UFormField>

    <UFormField label="Monto" name="amount" required>
      <UInputNumber
        id="finance-amount"
        :model-value="amount ?? undefined"
        name="amount"
        :min="0.01"
        :step="0.01"
        :increment="false"
        :decrement="false"
        class="w-full"
        placeholder="0,00"
        @update:model-value="emit('update:amount', ($event as number | undefined) ?? null)"
      />
    </UFormField>

    <UFormField label="Categoría" name="category">
      <template #label>
        <div class="flex items-center justify-between gap-3">
          <span>Categoría</span>
          <FinanceCategoryCreateButton
            :type="type"
            :create-category="createFinanceCategory"
            @created="emit('update:categoryId', $event.id)"
          />
        </div>
      </template>

      <FinanceCategorySelect
        :model-value="categoryId"
        :type="type"
        :categories="categories"
        @update:model-value="emit('update:categoryId', $event)"
      />

      <div class="mt-2 flex justify-end gap-2">
        <FinanceCategoryEditButton
          :category="selectedCategory"
          :update-category="updateFinanceCategory"
          @updated="emit('update:categoryId', $event.id)"
        />
        <FinanceCategoryDeleteButton
          :category="selectedCategory"
          :delete-category="deleteFinanceCategory"
          @deleted="clearCategoryIfDeleted"
        />
      </div>
    </UFormField>

    <UFormField label="Descripción" name="description" class="md:col-span-2">
      <UTextarea
        id="finance-description"
        :model-value="description"
        name="description"
        placeholder="Detalle adicional opcional del movimiento."
        @update:model-value="emit('update:description', $event)"
      />
    </UFormField>
  </div>
</template>
