<script setup lang="ts">
import type { FinanceCategory } from "@freelance/contracts";

const props = defineProps<{
  category: FinanceCategory | null;
  deleteCategory: (id: number) => Promise<void>;
}>();

const emit = defineEmits<{
  deleted: [id: number];
}>();

const open = ref(false);
const submitting = ref(false);
const toast = useToast();
const { toastApiError } = useApiError();

watch(
  () => props.category,
  (category) => {
    if (!category) {
      open.value = false;
    }
  },
);

async function handleDelete(): Promise<void> {
  if (!props.category) return;

  const categoryId = props.category.id;

  submitting.value = true;
  try {
    await props.deleteCategory(categoryId);
    open.value = false;
    emit("deleted", categoryId);
    toast.add({ title: "Categoría eliminada", color: "success" });
  } catch (error) {
    toastApiError(error, { fallback: "No se pudo eliminar la categoría." });
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Eliminar categoría"
    :description="category ? `Se eliminará la categoría ${category.name}. Los movimientos conservarán su snapshot histórico.` : ''"
    :ui="{ content: 'max-w-md', footer: 'justify-end' }"
  >
    <UButton
      label="Eliminar"
      icon="i-lucide-trash"
      color="error"
      variant="ghost"
      size="xs"
      :disabled="!category"
    />

    <template #footer>
      <div class="flex gap-2">
        <UButton label="Cancelar" variant="outline" color="neutral" @click="open = false" />
        <UButton label="Eliminar" color="error" :loading="submitting" @click="handleDelete" />
      </div>
    </template>
  </UModal>
</template>
