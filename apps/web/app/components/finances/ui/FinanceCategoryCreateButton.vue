<script setup lang="ts">
import type { FinanceCategory, FinanceEntryType } from "@freelance/contracts";

const props = defineProps<{
  createCategory: (input: { type: FinanceEntryType; name: string }) => Promise<FinanceCategory>;
  type: FinanceEntryType;
}>();

const emit = defineEmits<{
  created: [category: FinanceCategory];
}>();

const open = ref(false);
const name = ref("");
const submitting = ref(false);
const toast = useToast();
const { toastApiError } = useApiError();

const placeholder = computed(() =>
  props.type === "expense" ? "Ej. Suscripciones SaaS" : "Ej. Reembolso",
);

function resetForm(): void {
  name.value = "";
}

async function handleSubmit(): Promise<void> {
  if (!name.value.trim()) {
    toast.add({ title: "Ingresa un nombre de categoría", color: "warning" });
    return;
  }

  submitting.value = true;
  try {
    const category = await props.createCategory({
      type: props.type,
      name: name.value.trim(),
    });
    emit("created", category);
    toast.add({ title: "Categoría creada", color: "success" });
    open.value = false;
    resetForm();
  } catch (error) {
    toastApiError(error, { fallback: "No se pudo crear la categoría." });
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Nueva categoría"
    description="Crea una categoría reutilizable para movimientos de este tipo."
    :ui="{ content: 'max-w-md', footer: 'justify-end' }"
  >
    <UButton
      label="Nueva categoría"
      icon="i-lucide-plus"
      color="neutral"
      variant="ghost"
      size="xs"
    />

    <template #body>
      <form class="space-y-4" @submit.prevent="handleSubmit">
        <UFormField label="Nombre visible" name="finance_category_name" required>
          <UInput
            id="finance-category-new"
            v-model="name"
            name="finance_category_name"
            class="w-full"
            :placeholder="placeholder"
            autocomplete="off"
          />
        </UFormField>
      </form>
    </template>

    <template #footer>
      <div class="flex gap-2">
        <UButton label="Cancelar" variant="outline" color="neutral" @click="open = false; resetForm()" />
        <UButton label="Crear categoría" :loading="submitting" @click="handleSubmit" />
      </div>
    </template>
  </UModal>
</template>
