<script setup lang="ts">
import { CalendarDate } from "@internationalized/date";
import { useFinancesApi } from "~/composables/finances/useFinancesApi";

definePageMeta({ layout: "default" });

const route = useRoute();
const router = useRouter();
const toast = useToast();
const { toastApiError } = useApiError();
const { findEntry, updateEntry } = useFinancesApi();

const entryId = computed(() => Number(route.params.id));
const saving = ref(false);
const amount = ref<number | null>(null);
const occurredOn = ref("");
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

const { data: entry, status } = useAsyncData(
  () => `finance-entry-${entryId.value}`,
  () => findEntry(entryId.value),
  { watch: [entryId] },
);

watchEffect(() => {
  if (!entry.value) return;
  amount.value = entry.value.amount_cents / 100;
  occurredOn.value = entry.value.occurred_on;
  description.value = entry.value.description ?? "";
  category.value = entry.value.category ?? "";
});

async function onSubmit() {
  if (!entry.value?.is_manual) {
    toast.add({ title: "Solo movimientos manuales se pueden editar", color: "warning" });
    return;
  }

  if (!amount.value || amount.value <= 0) {
    toast.add({ title: "Ingresa un monto válido", color: "warning" });
    return;
  }

  saving.value = true;
  try {
    await updateEntry(entryId.value, {
      amount_cents: Math.round(amount.value * 100),
      occurred_on: occurredOn.value,
      description: description.value.trim(),
      category: category.value.trim() || null,
    });
    toast.add({ title: "Movimiento actualizado", color: "success" });
    await router.push("/finances");
  } catch (error) {
    toastApiError(error, { fallback: "No se pudo actualizar el movimiento." });
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <UDashboardPanel id="finances-entry-edit">
    <template #header>
      <UDashboardNavbar title="Editar movimiento">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <UCard v-if="status === 'pending'">
        <div class="py-8 text-center text-muted">Cargando...</div>
      </UCard>
      <UCard v-else-if="!entry">
        <div class="py-8 text-center text-muted">Movimiento no encontrado.</div>
      </UCard>
      <UCard v-else class="w-full max-w-2xl">
        <form class="space-y-4" @submit.prevent="onSubmit">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
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
              <UInput id="finance-category" v-model="category" name="category" />
            </UFormField>

            <UFormField label="Descripción" name="description" required class="md:col-span-2">
              <UTextarea id="finance-description" v-model="description" name="description" />
            </UFormField>
          </div>

          <div class="flex items-center gap-2">
            <div class="flex-1" />
            <UButton variant="outline" @click="router.push('/finances')">Cancelar</UButton>
            <UButton type="submit" :loading="saving">Actualizar</UButton>
          </div>
        </form>
      </UCard>
    </template>
  </UDashboardPanel>
</template>
