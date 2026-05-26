import type { Client } from "@freelance/contracts";
import { useClientsApi } from "~/composables/clients/useClientsApi";
import { useClientForm } from "~/composables/clients/useClientForm";

/** Orquesta detalle/edición de cliente: carga, formulario, avatar y guardado. */
export function useClientDetailPage(clientId: Ref<number>) {
  const route = useRoute();
  const router = useRouter();
  const { find, update, uploadAvatar } = useClientsApi();
  const toast = useToast();
  const { toastApiError } = useApiError();

  const { data, status, refresh } = useAsyncData(
    () => `client-${clientId.value}`,
    () => find(clientId.value),
    { watch: [clientId] },
  );

  const client = computed(() => data.value);
  const editing = ref(false);
  const saving = ref(false);
  const form = useClientForm();
  const avatarPreviewUrl = ref<string | null>(null);

  function hydrateFormFromClient(value: Client) {
    form.name.value = value.name;
    form.email.value = value.email ?? "";
    form.phone.value = value.phone ?? "";
    form.taxId.value = value.tax_id ?? "";
    form.address.value = value.address ?? "";
    form.notes.value = value.notes ?? "";
    form.avatar.value = value.avatar ?? "";
    avatarPreviewUrl.value = value.avatar ?? null;
  }

  function startEdit() {
    if (!client.value) {
      return;
    }

    hydrateFormFromClient(client.value);
    editing.value = true;
  }

  function cancelEdit() {
    editing.value = false;
  }

  async function consumeEditQueryMode() {
    if (route.query.mode !== "edit") {
      return;
    }

    const { mode: _mode, ...query } = route.query;

    await router.replace({
      query,
    });
  }

  watch(
    data,
    async () => {
      if (route.query.mode === "edit" && client.value && !editing.value) {
        startEdit();
        await consumeEditQueryMode();
      }
    },
    { immediate: true },
  );

  async function onSave() {
    saving.value = true;

    try {
      await update(clientId.value, form.toUpdateInput());
      toast.add({ title: "Cliente actualizado" });
      editing.value = false;
      await refresh();
    } catch (error) {
      toastApiError(error, { fallback: "No se pudo guardar los cambios." });
    } finally {
      saving.value = false;
    }
  }

  async function onFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (!file || !client.value) {
      return;
    }

    avatarPreviewUrl.value = URL.createObjectURL(file);

    try {
      const updated = await uploadAvatar(clientId.value, file);
      toast.add({ title: "Avatar actualizado" });
      form.avatar.value = updated.avatar ?? "";
      await refresh();
    } catch (error) {
      toastApiError(error, { fallback: "No se pudo subir la imagen." });
    }

    if (target) {
      target.value = "";
    }
  }

  return {
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
  };
}
