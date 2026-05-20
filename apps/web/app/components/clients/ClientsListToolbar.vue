<script setup lang="ts">
  import { upperFirst } from "scule";
  import type { ClientsTableApi } from "~/types/clients-table";
  import ClientsDeleteModal from "~/components/clients/ClientsDeleteModal.vue";

  const searchQuery = defineModel<string>("searchQuery", { required: true });

  defineProps<{
    selectedCount: number;
    tableApi: ClientsTableApi | null;
  }>();

  const emit = defineEmits<{
    confirmBatchDelete: [];
  }>();
</script>

<template>
  <div class="flex flex-wrap items-center justify-between gap-1.5">
    <UInput id="clients-search" name="clients-search" v-model="searchQuery" type="search" class="max-w-sm"
      icon="i-lucide-search" placeholder="Buscar clientes..." autocomplete="off" aria-label="Buscar clientes" />

    <div class="flex flex-wrap items-center gap-1.5">
      <ClientsDeleteModal :count="selectedCount" @confirm="emit('confirmBatchDelete')">
        <UButton v-if="selectedCount" label="Eliminar" color="error" variant="subtle" icon="i-lucide-trash">
          <template #trailing>
            <UKbd>{{ selectedCount }}</UKbd>
          </template>
        </UButton>
      </ClientsDeleteModal>

      <UDropdownMenu v-if="tableApi" :items="tableApi
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
        " :content="{ align: 'end' }">
        <UButton label="Columnas" color="neutral" variant="outline" trailing-icon="i-lucide-settings-2" />
      </UDropdownMenu>
    </div>
  </div>
</template>
