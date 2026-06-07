<script setup lang="ts">
import type { FinanceCategory, FinanceEntryType } from "@freelance/contracts";

const props = defineProps<{
  categories: FinanceCategory[];
  modelValue: number | null;
  type: FinanceEntryType;
}>();

const emit = defineEmits<{
  "update:modelValue": [value: number | null];
}>();

const items = computed(() =>
  props.categories
    .filter((category) => category.type === props.type)
    .map((category) => ({
      value: category.id,
      label: category.name,
      description: category.slug,
    })),
);
</script>

<template>
  <USelectMenu
    id="finance-category"
    name="category_id"
    class="w-full"
    :items="items"
    :model-value="modelValue ?? undefined"
    value-key="value"
    label-key="label"
    description-key="description"
    :search-input="{ placeholder: 'Buscar categoría...' }"
    :filter-fields="['label', 'description']"
    placeholder="Selecciona una categoría"
    clear
    @update:model-value="emit('update:modelValue', ($event as number | undefined) ?? null)"
  />
</template>
