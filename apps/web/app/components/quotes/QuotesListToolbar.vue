<script setup lang="ts">
import { upperFirst } from "scule";
import type { QuotesTableApi } from "~/types/quotes-table";
import QuotesDeleteModal from "~/components/quotes/QuotesDeleteModal.vue";
import PageToolbarShell from "~/components/ui/PageToolbarShell.vue";

const searchQuery = defineModel<string>("searchQuery", { required: true });

defineProps<{
  selectedCount: number;
  tableApi: QuotesTableApi | null;
}>();

const emit = defineEmits<{
  confirmBatchDelete: [];
}>();
</script>

<template>
  <PageToolbarShell>
    <UInput
      id="quotes-search"
      name="quotes-search"
      v-model="searchQuery"
      type="search"
      class="max-w-sm"
      icon="i-lucide-search"
      placeholder="Buscar cotizaciones..."
      autocomplete="off"
      aria-label="Buscar cotizaciones"
    />

    <div class="flex flex-wrap items-center gap-1.5">
      <QuotesDeleteModal
        :count="selectedCount"
        @confirm="emit('confirmBatchDelete')"
      >
        <UButton
          v-if="selectedCount"
          label="Eliminar"
          color="error"
          variant="subtle"
          icon="i-lucide-trash"
        >
          <template #trailing>
            <UKbd>{{ selectedCount }}</UKbd>
          </template>
        </UButton>
      </QuotesDeleteModal>

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
  </PageToolbarShell>
</template>
