<script setup lang="ts">
import { upperFirst } from "scule";
import type { FinancesTableApi } from "~/types/finances-table";

const searchQuery = defineModel<string>("searchQuery", { required: true });

defineProps<{
  tableApi: FinancesTableApi | null;
}>();
</script>

<template>
  <div class="flex flex-wrap items-center justify-between gap-1.5">
    <UInput
      id="finances-search"
      name="finances-search"
      v-model="searchQuery"
      type="search"
      class="max-w-sm"
      icon="i-lucide-search"
      placeholder="Buscar movimientos..."
      autocomplete="off"
      aria-label="Buscar movimientos"
    />

    <div class="flex flex-wrap items-center gap-1.5">
      <UDropdownMenu
        v-if="tableApi"
        :items="tableApi
          .getAllColumns()
          .filter((column) => column.getCanHide())
          .map((column) => ({
            label: upperFirst(column.id),
            type: 'checkbox' as const,
            checked: column.getIsVisible(),
            onUpdateChecked(checked: boolean) {
              tableApi?.getColumn(column.id)?.toggleVisibility(!!checked);
            },
            onSelect(e?: Event) {
              e?.preventDefault();
            },
          }))
        "
        :content="{ align: 'end' }"
      >
        <UButton label="Columnas" color="neutral" variant="outline" trailing-icon="i-lucide-settings-2" />
      </UDropdownMenu>
    </div>
  </div>
</template>
