<script setup lang="ts">
import FinanceEntryFormFields from "~/components/finances/ui/FinanceEntryFormFields.vue";
import PageContentNarrow from "~/components/ui/PageContentNarrow.vue";
import PageSectionCard from "~/components/ui/PageSectionCard.vue";
import PageStateCard from "~/components/ui/PageStateCard.vue";
import { useFinanceCategories } from "~/composables/finances/useFinanceCategories";
import { useFinancesApi } from "~/composables/finances/useFinancesApi";

definePageMeta({ layout: "default" });

const route = useRoute();
const router = useRouter();
const toast = useToast();
const { toastApiError } = useApiError();
const { findEntry, updateEntry } = useFinancesApi();

const entryId = computed(() => Number(route.params.id));
const saving = ref(false);
const type = ref<"income" | "expense">("expense");
const amount = ref<number | null>(null);
const occurredOn = ref("");
const name = ref("");
const description = ref("");
const categoryId = ref<number | null>(null);

const { data: entry, status } = useAsyncData(
  () => `finance-entry-${entryId.value}`,
  () => findEntry(entryId.value),
  { watch: [entryId] },
);

const {
  categories,
  createFinanceCategory,
  updateFinanceCategory,
  deleteFinanceCategory,
} = useFinanceCategories(type);

watch(categories, (currentCategories) => {
  if (categoryId.value === null) return;

  const exists = currentCategories.some((category) => category.id === categoryId.value);
  if (!exists) {
    categoryId.value = null;
  }
});

watchEffect(() => {
  if (!entry.value) return;
  type.value = entry.value.type;
  amount.value = entry.value.amount_cents / 100;
  occurredOn.value = entry.value.occurred_on;
  name.value = entry.value.name;
  description.value = entry.value.description ?? "";
  categoryId.value = entry.value.category_id;
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

  if (!name.value.trim()) {
    toast.add({ title: "Ingresa un nombre", color: "warning" });
    return;
  }

  saving.value = true;
  try {
    await updateEntry(entryId.value, {
      type: type.value,
      amount_cents: Math.round(amount.value * 100),
      occurred_on: occurredOn.value,
      name: name.value.trim(),
      description: description.value.trim() || null,
      category_id: categoryId.value,
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
      <PageContentNarrow>
        <PageStateCard v-if="status === 'pending'" message="Cargando..." />
        <PageStateCard v-else-if="!entry" message="Movimiento no encontrado." />
        <PageSectionCard v-else>
          <form class="space-y-4" @submit.prevent="onSubmit">
            <FinanceEntryFormFields
              :type="type"
              :amount="amount"
              :occurred-on="occurredOn"
              :name="name"
              :description="description"
              :category-id="categoryId"
              :categories="categories"
              :create-finance-category="createFinanceCategory"
              :update-finance-category="updateFinanceCategory"
              :delete-finance-category="deleteFinanceCategory"
              @update:type="type = $event"
              @update:amount="amount = $event"
              @update:occurred-on="occurredOn = $event"
              @update:name="name = $event"
              @update:description="description = $event"
              @update:category-id="categoryId = $event"
            />

            <div class="flex items-center gap-2">
              <div class="flex-1" />
              <UButton variant="outline" @click="router.push('/finances')">Cancelar</UButton>
              <UButton type="submit" :loading="saving">Actualizar</UButton>
            </div>
          </form>
        </PageSectionCard>
      </PageContentNarrow>
    </template>
  </UDashboardPanel>
</template>
