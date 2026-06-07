<script setup lang="ts">
import FinanceEntryFormFields from "~/components/finances/ui/FinanceEntryFormFields.vue";
import { useFinanceCategories } from "~/composables/finances/useFinanceCategories";
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
const name = ref("");
const description = ref("");
const categoryId = ref<number | null>(null);
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

async function onSubmit() {
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
    await createEntry({
      type: type.value,
      amount_cents: Math.round(amount.value * 100),
      occurred_on: occurredOn.value,
      name: name.value.trim(),
      description: description.value.trim() || null,
      category_id: categoryId.value,
    });
    toast.add({ title: "Movimiento creado", color: "success" });
    await router.push("/finances");
  } catch (error) {
    toastApiError(error, { fallback: "No se pudo crear el movimiento." });
  } finally {
    saving.value = false;
  }
}

watch(type, () => {
  categoryId.value = null;
});
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
            <UButton type="submit" :loading="saving">Guardar</UButton>
          </div>
        </form>
      </UCard>
    </template>
  </UDashboardPanel>
</template>
