<script setup lang="ts">
definePageMeta({ layout: "default" });

const route = useRoute();
const router = useRouter();

const id = computed(() => Number(route.params.id));

const {
  client,
  status,
  editing,
  saving,
  form,
  avatarPreviewUrl,
  startEdit,
  cancelEdit,
  onSave,
  onFileChange,
} = useClientDetailPage(id);
</script>

<template>
  <UDashboardPanel id="clients-detail">
    <template #header>
      <UDashboardNavbar title="Detalle del cliente">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            label="Volver"
            icon="i-lucide-arrow-left"
            variant="ghost"
            @click="router.push('/clients')"
          />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <UCard v-if="status === 'pending'">
        <div class="py-8 text-center text-muted">Cargando...</div>
      </UCard>

      <UCard v-else-if="!client">
        <div class="py-8 text-center text-muted">Cliente no encontrado.</div>
      </UCard>

      <UCard v-else>
        <ClientsSectionsClientEditForm
          v-if="editing"
          :name="form.name.value"
          :email="form.email.value"
          :phone="form.phone.value"
          :tax-id="form.taxId.value"
          :address="form.address.value"
          :notes="form.notes.value"
          :avatar-preview="avatarPreviewUrl"
          :saving="saving"
          @update:name="form.name.value = $event"
          @update:email="form.email.value = $event"
          @update:phone="form.phone.value = $event"
          @update:tax-id="form.taxId.value = $event"
          @update:address="form.address.value = $event"
          @update:notes="form.notes.value = $event"
          @save="onSave"
          @cancel="cancelEdit"
          @file-change="onFileChange"
        />

        <ClientsSectionsClientDetailView
          v-else
          :client="client"
          @edit="startEdit"
        />
      </UCard>
    </template>
  </UDashboardPanel>
</template>
