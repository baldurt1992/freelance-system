<script setup lang="ts">
import type { FinanceCategory } from "@freelance/contracts";

const props = defineProps<{
  category: FinanceCategory | null;
  updateCategory: (id: number, input: { name: string }) => Promise<FinanceCategory>;
}>();

const emit = defineEmits<{
  updated: [category: FinanceCategory];
}>();

const open = ref(false);
const name = ref("");
const submitting = ref(false);
const toast = useToast();
const { toastApiError } = useApiError();

watch(
  () => props.category,
  (category) => {
    name.value = category?.name ?? "";
  },
  { immediate: true },
);

async function handleSubmit(): Promise<void> {
  if (!props.category) return;

  if (!name.value.trim()) {
    toast.add({ title: "Ingresa un nombre de categoría", color: "warning" });
    return;
  }

  submitting.value = true;
  try {
    const category = await props.updateCategory(props.category.id, { name: name.value.trim() });
    emit("updated", category);
    toast.add({ title: "Categoría actualizada", color: "success" });
    open.value = false;
  } catch (error) {
    toastApiError(error, { fallback: "No se pudo actualizar la categoría." });
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Editar categoría"
    description="Actualiza el nombre visible y el slug asociado a esta categoría."
    :ui="{ content: 'max-w-md', footer: 'justify-end' }"
  >
    <UButton
      label="Editar"
      icon="i-lucide-pencil"
      color="neutral"
      variant="ghost"
      size="xs"
      :disabled="!category"
    />

    <template #body>
      <form class="space-y-4" @submit.prevent="handleSubmit">
        <UFormField label="Nombre visible" name="finance_category_edit_name" required>
          <UInput
            id="finance-category-edit"
            v-model="name"
            name="finance_category_edit_name"
            class="w-full"
            autocomplete="off"
          />
        </UFormField>
      </form>
    </template>

    <template #footer>
      <div class="flex gap-2">
        <UButton label="Cancelar" variant="outline" color="neutral" @click="open = false" />
        <UButton label="Guardar cambios" :loading="submitting" @click="handleSubmit" />
      </div>
    </template>
  </UModal>
</template>
