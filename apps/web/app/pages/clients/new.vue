<script setup lang="ts">
import { useClientsApi } from "~/composables/clients/useClientsApi";
import { useClientForm } from "~/composables/clients/useClientForm";
import ClientFormFields from "~/components/clients/ui/ClientFormFields.vue";
import PageContentNarrow from "~/components/ui/PageContentNarrow.vue";
import PageSectionCard from "~/components/ui/PageSectionCard.vue";

definePageMeta({ layout: "default" });

const { create, uploadAvatar } = useClientsApi();
const toast = useToast();
const router = useRouter();
const form = useClientForm();
const { toastApiError } = useApiError();

const saving = ref(false);
const avatarFile = ref<File | null>(null);
const avatarPreviewUrl = ref<string | null>(null);
const fileInput = useTemplateRef<HTMLInputElement>("fileInput");

function onFileChange(event: Event) {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0] ?? null;
  avatarFile.value = file;
  if (file) {
    avatarPreviewUrl.value = URL.createObjectURL(file);
  }
  if (target) target.value = "";
}

async function onSubmit() {
  saving.value = true;
  try {
    const client = await create(form.toCreateInput());
    if (avatarFile.value && client) {
      await uploadAvatar(client.id, avatarFile.value);
    }
    toast.add({ title: "Cliente creado" });
    await router.push("/clients");
  } catch (error) {
    toastApiError(error, { fallback: "No se pudo crear el cliente." });
  } finally {
    saving.value = false;
    avatarFile.value = null;
    avatarPreviewUrl.value = null;
  }
}
</script>

<template>
  <UDashboardPanel id="clients-new">
    <template #header>
      <UDashboardNavbar title="Nuevo cliente">
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
      <PageContentNarrow>
        <PageSectionCard>
          <form class="space-y-6" @submit.prevent="onSubmit">
            <ClientFormFields
              :name="form.name.value"
              :email="form.email.value"
              :phone="form.phone.value"
              :tax-id="form.taxId.value"
              :address="form.address.value"
              :notes="form.notes.value"
              :avatar-preview="avatarPreviewUrl"
              @update:name="form.name.value = $event"
              @update:email="form.email.value = $event"
              @update:phone="form.phone.value = $event"
              @update:tax-id="form.taxId.value = $event"
              @update:address="form.address.value = $event"
              @update:notes="form.notes.value = $event"
            />

            <div class="flex items-center gap-3">
              <input
                ref="fileInput"
                type="file"
                accept="image/*"
                class="hidden"
                @change="onFileChange"
              />
              <UButton
                :label="avatarFile ? `Avatar: ${avatarFile.name}` : 'Subir Avatar'"
                icon="i-lucide-upload"
                variant="outline"
                @click="fileInput?.click()"
              />
              <div class="flex-1" />
              <UButton variant="outline" @click="router.push('/clients')">
                Cancelar
              </UButton>
              <UButton type="submit" :loading="saving">
                Guardar
              </UButton>
            </div>
          </form>
        </PageSectionCard>
      </PageContentNarrow>
    </template>
  </UDashboardPanel>
</template>
