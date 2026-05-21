<script setup lang="ts">
import type { Client } from "@freelance/contracts";
import { useClientsApi } from "~/composables/clients/useClientsApi";
import { useClientForm } from "~/composables/clients/useClientForm";
import ClientFormFields from "~/components/clients/ui/ClientFormFields.vue";

definePageMeta({ layout: "default" });

const route = useRoute();
const router = useRouter();
const { find, update, uploadAvatar } = useClientsApi();
const toast = useToast();
const { toastApiError } = useApiError();

const id = computed(() => Number(route.params.id));

const { data, status, refresh } = useAsyncData(
  `client-${id.value}`,
  () => find(id.value),
  { watch: [id] },
);

const client = computed(() => data.value);

const editing = ref(false);
const saving = ref(false);

const form = useClientForm();
const avatarPreviewUrl = ref<string | null>(null);

function startEdit() {
  if (!client.value) return;
  form.name.value = client.value.name;
  form.email.value = client.value.email ?? "";
  form.phone.value = client.value.phone ?? "";
  form.taxId.value = client.value.tax_id ?? "";
  form.address.value = client.value.address ?? "";
  form.notes.value = client.value.notes ?? "";
  form.avatar.value = client.value.avatar ?? "";
  avatarPreviewUrl.value = client.value.avatar ?? null;
  editing.value = true;
}

const fileInput = useTemplateRef<HTMLInputElement>("fileInput");

async function onFileChange(event: Event) {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (!file || !client.value) return;
  avatarPreviewUrl.value = URL.createObjectURL(file);
  try {
    const updated = await uploadAvatar(id.value, file);
    toast.add({ title: "Avatar actualizado" });
    form.avatar.value = updated.avatar ?? "";
    await refresh();
  } catch (error) {
    toastApiError(error, { fallback: "No se pudo subir la imagen." });
  }
  if (target) target.value = "";
}

watch(
  data,
  () => {
    if (route.query.mode === "edit") {
      startEdit();
    }
  },
  { immediate: true },
);

async function onSave() {
  saving.value = true;
  try {
    await update(id.value, form.toUpdateInput());
    toast.add({ title: "Cliente actualizado" });
    editing.value = false;
    await refresh();
  } catch (error) {
    toastApiError(error, { fallback: "No se pudo guardar los cambios." });
  } finally {
    saving.value = false;
  }
}
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
        <div class="text-center py-8 text-muted">Cargando...</div>
      </UCard>

      <UCard v-else-if="!client">
        <div class="text-center py-8 text-muted">Cliente no encontrado.</div>
      </UCard>

      <UCard v-else>
        <template v-if="editing">
          <form class="space-y-6" @submit.prevent="onSave">
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
                label="Subir Avatar"
                icon="i-lucide-upload"
                variant="outline"
                @click="fileInput?.click()"
              />
              <div class="flex-1" />
              <UButton variant="outline" @click="editing = false">Cancelar</UButton>
              <UButton type="submit" :loading="saving">Guardar</UButton>
            </div>
          </form>
        </template>

        <template v-else>
          <div class="flex items-center gap-4 mb-6">
            <UAvatar
              :src="client.avatar || undefined"
              :alt="client.name"
              size="3xl"
            />
            <div>
              <h2 class="text-2xl font-semibold">{{ client.name }}</h2>
              <p v-if="client.email" class="text-sm text-muted">{{ client.email }}</p>
            </div>
            <div class="flex-1" />
            <UButton
              icon="i-lucide-pencil"
              @click="startEdit"
            >
              Editar
            </UButton>
          </div>
          <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <dt class="text-sm text-muted">Teléfono</dt>
              <dd>{{ client.phone || "—" }}</dd>
            </div>
            <div>
              <dt class="text-sm text-muted">NIT / CC</dt>
              <dd>{{ client.tax_id || "—" }}</dd>
            </div>
            <div>
              <dt class="text-sm text-muted">Dirección</dt>
              <dd>{{ client.address || "—" }}</dd>
            </div>
          </dl>
          <div v-if="client.notes" class="mt-4">
            <dt class="text-sm text-muted">Notas</dt>
            <dd class="whitespace-pre-wrap">{{ client.notes }}</dd>
          </div>
        </template>
      </UCard>
    </template>
  </UDashboardPanel>
</template>
