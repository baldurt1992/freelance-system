<script setup lang="ts">
import { CalendarDate } from "@internationalized/date";
import { useFinancesApi } from "~/composables/finances/useFinancesApi";

definePageMeta({ layout: "default" });

const route = useRoute();
const router = useRouter();
const toast = useToast();
const { toastApiError } = useApiError();
const { createEntry } = useFinancesApi();

const saving = ref(false);
const type = ref<"income" | "expense">(route.query.type === "expense" ? "expense" : "income");
const amount = ref<number | null>(null);
const occurredOn = ref(new Date().toISOString().slice(0, 10));
const description = ref("");
const category = ref("");

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
  if (!amount.value || amount.value <= 0) {
    toast.add({ title: "Ingresa un monto válido", color: "warning" });
    return;
  }

  if (!description.value.trim()) {
    toast.add({ title: "Ingresa una descripción", color: "warning" });
    return;
  }

  saving.value = true;
  try {
    await createEntry({
      type: type.value,
      amount_cents: Math.round(amount.value * 100),
      occurred_on: occurredOn.value,
      description: description.value.trim(),
      category: category.value.trim() || null,
    });
    toast.add({ title: "Movimiento creado", color: "success" });
    await router.push("/finances");
  } catch (error) {
    toastApiError(error, { fallback: "No se pudo crear el movimiento." });
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <UDashboardPanel id="finances-entry-new">
    <template #header>
      <UDashboardNavbar title="Nuevo movimiento">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <UCard class="w-full max-w-2xl">
        <form class="space-y-4" @submit.prevent="onSubmit">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <UFormField label="Tipo" name="type" required>
              <USelect
                id="finance-type"
                name="type"
                :model-value="type"
                :items="[{ label: 'Ingreso', value: 'income' }, { label: 'Gasto', value: 'expense' }]"
                @update:model-value="type = ($event as 'income' | 'expense') ?? 'income'"
              />
            </UFormField>

            <UFormField label="Fecha" name="occurred_on" required>
              <UInputDate
                id="finance-occurred-on"
                name="occurred_on"
                :model-value="parseDateToCalendarDate(occurredOn)"
                @update:model-value="occurredOn = calendarDateToString($event as CalendarDate | undefined) ?? occurredOn"
              />
            </UFormField>

            <UFormField label="Monto" name="amount" required>
              <UInput id="finance-amount" v-model="amount" name="amount" type="number" :min="0" :step="0.01" />
            </UFormField>

            <UFormField label="Categoría" name="category">
              <UInput id="finance-category" v-model="category" name="category" placeholder="Ej. ai_tools" />
            </UFormField>

            <UFormField label="Descripción" name="description" required class="md:col-span-2">
              <UTextarea id="finance-description" v-model="description" name="description" />
            </UFormField>
          </div>

          <div class="flex items-center gap-2">
            <div class="flex-1" />
            <UButton variant="outline" @click="router.push('/finances')">Cancelar</UButton>
            <UButton type="submit" :loading="saving">Guardar</UButton>
          </div>
        </form>
      </UCard>
    </template>
  </UDashboardPanel>
</template>
